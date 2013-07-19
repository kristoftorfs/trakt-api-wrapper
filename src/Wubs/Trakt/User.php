<?php namespace Wubs\Trakt;

use Wubs\Trakt\Exceptions\TraktException;

class User{

	private $data = array();

	private $username;

	private $inputDateFormat = 'Y-m-d';

	private $traktDateFormat = 'Ymd';

	public function __construct($username){
		$this->username = $username;
		$this->data['user/profile'] = Trakt::get('user/profile')->setUsername($username)->run();
	}

	public function getCalendar($date = false, $days = false){
		$calendar = Trakt::get('user/calendar/shows')->setUsername($this->username);
		// check if the date provided is a valid date
		if($date){
			if($this->checkDate($date)){
				$date = $this->convertDate();
				$calendar->setDate($date);
			}
		}
		$dates = $calendar->run();
		foreach ($dates as $date) {
			foreach ($date['episodes'] as $episode) {
				$this->mapEpisode($episode); //Episode object is a child of the show object...
			}
		}
	}

	private function checkDate($date){
		$this->dateObject = \DateTime::createFromFormat($this->inputDateFormat, $date);
		$check = ($this->dateObject && (date_format($this->dateObject,$this->inputDateFormat) == $date)) ? true : false;
		if(!$check){
			throw new TraktException("Date provided is not valid", 1);
		}
		else{
			return true;
		}
	}

	private function convertDate(){
		return date_format($this->dateObject, $this->traktDateFormat);
	}

	private function mapEpisode($episodeData){
		print_r($episodeData);
	}
}
?>