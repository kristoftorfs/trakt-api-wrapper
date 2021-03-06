<?php namespace Wubs\Trakt\Request\Calendars;

use Carbon\Carbon;
use Wubs\Trakt\Request\AbstractRequest;
use Wubs\Trakt\Request\Parameters\Days;
use Wubs\Trakt\Request\Parameters\StartDate;
use Wubs\Trakt\Request\Parameters\TimePeriod;
use Wubs\Trakt\Request\RequestType;
use Wubs\Trakt\Response\Handlers\Calendars\Shows as ShowsResponse;

/**
 * Created by PhpStorm.
 * User: bwubs
 * Date: 25/02/15
 * Time: 15:10
 */
class Shows extends AbstractRequest
{
    use TimePeriod;

    /**
     * @param StartDate $startDate
     * @param Days $days
     */
    public function __construct(StartDate $startDate = null, Days $days = null)
    {
        parent::__construct();
        $this->setDays($days);
        $this->setStartDate($startDate);

        $this->setResponseHandler(new ShowsResponse());
    }

    public function getRequestType()
    {
        return RequestType::GET;
    }

    public function getUri()
    {
        return "calendars/shows/:start_date/:days";
    }


}