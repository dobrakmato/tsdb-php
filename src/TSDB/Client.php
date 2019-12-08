<?php

namespace TSDB;

class Client
{
    /**
     * @var resource
     */
    private $socket = null;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var int
     */
    private $port;

    /**
     * TSDB constructor.
     * @param string $hostname hostname of TSDB server to connect to
     * @param int $port port of TSDB server to connect to
     */
    public function __construct(string $hostname, int $port)
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->recreateConnection();
    }

    private function recreateConnection()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
            throw new TSDBException("Cannot create socket.");
        }

        if (!socket_connect($socket, $this->hostname, $this->port)) {
            $this->throwException("Cannot connect to the server.");
        }

        // todo: authenticate

        $this->socket = $socket;
    }

    /**
     * @param string $message
     */
    private function throwException(string $message)
    {
        $errCode = socket_last_error($this->socket);
        $errText = socket_strerror($errCode);

        if ($errCode !== 0) {
            $exception = "$message Detail: $errText";
        } else {
            $exception = $message;
        }

        throw new TSDBException($exception);
    }

    /**
     * @param string $name
     * @throws TSDBException
     */
    public function createSeries(string $name)
    {
        $this->query([
            "CreateSeries" => $name
        ]);
    }

    /**
     * @param string $series
     * @param $value
     * @throws TSDBException
     */
    public function insertValue(string $series, $value)
    {
        $this->query([
            "Insert" => [
                "to" => $series,
                "value" => $value
            ]
        ]);
    }

    /**
     * @param string $series
     * @param int|null $fromTimestamp
     * @param int|null $toTimestamp
     * @return array
     * @throws TSDBException
     */
    public function select(string $series, int $fromTimestamp = null, int $toTimestamp = null): array
    {
        $response = $this->query([
            "Select" => [
                "from" => $series,
                "between" => [
                    "min" => $fromTimestamp,
                    "max" => $toTimestamp,
                ]
            ]
        ]);

        return $response['Data'];
    }

    /**
     * @param array $request
     * @return array
     */
    public function query(array $request): array
    {
        $json = json_encode($request);

        if ($json === FALSE) {
            $this->throwException("Cannot JSON-encode request object.");
        }

        $this->writeRequest($json);

        // todo: remove when server support multiple queries per connection
        socket_shutdown($this->socket, 1);

        $response = json_decode($this->readResponse(), true);

        // todo: remove when server support multiple queries per connection
        $this->close();
        $this->recreateConnection();

        switch ($response) {
            case "AuthError":
                $this->throwException("Authentication error.");
                break;
            case "InvalidQuery":
                $this->throwException("Invalid query sent.");
                break;
            case "TableNotFound":
                $this->throwException("Table not found.");
                break;
            case "TableExists":
                $this->throwException("Table already exists.");
                break;
            case "Created":
            case "Inserted":
                return [];
        }

        return $response;
    }

    /**
     * @param string $json
     */
    private function writeRequest(string $json)
    {
        $bytes = strlen($json);
        while ($bytes > 0) {
            $written = socket_write($this->socket, $json);

            // socket_write may return zero bytes written
            if ($written === FALSE) {
                $this->throwException("Cannot write to socket.");
            }

            $bytes -= $written;
            $json = substr($json, $written);
        }
    }

    /**
     * @return string
     */
    private function readResponse(): string
    {
        $resp = "";

        while (($buff = socket_read($this->socket, 4096))) {
            $resp .= $buff;
        }

        if ($buff === FALSE) {
            $this->throwException("Cannot read from socket.");
        }

        return $resp;
    }

    public function close()
    {
        socket_close($this->socket);
    }
}
