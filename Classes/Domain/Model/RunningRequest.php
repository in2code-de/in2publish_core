<?php


namespace In2code\In2publishCore\Domain\Model;


class RunningRequest
{
    /**
    /**
     * @var int
     */
    protected $recordId = 0;

    /**
     * @var string
     */
    protected $tableName = 'pages';

    /**
     * @var string
     */
    protected $requestToken = '';

    /**
     * @var int
     */
    protected $timestampBegin = 0;

    public function __construct($recordId, $tableName, $requestToken, $timestampBegin = 0)
    {
        $this->recordId = $recordId;
        $this->tableName = $tableName;
        $this->requestToken = $requestToken;
        $this->timestampBegin = ($timestampBegin > 0 ? $timestampBegin : time());
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function setRecordId(int $recordId)
    {
        $this->recordId = $recordId;

    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName)
    {
        $this->tableName = $tableName;

    }

    public function getRequestToken(): string
    {
        return $this->requestToken;
    }

    public function setRequestToken(string $requestToken)
    {
        $this->requestToken = $requestToken;

    }

    public function getTimestampBegin(): int
    {
        return $this->timestampBegin;
    }

    public function setTimestampBegin(int $timestampBegin)
    {
        $this->timestampBegin = $timestampBegin;
    }

}
