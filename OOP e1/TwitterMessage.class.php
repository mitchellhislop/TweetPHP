<?
class TwitterMessage extends Data
{	
	public function __construct($id_twitter_message)
	{
		parent::__construct('twitter_message', $id_twitter_message);
	}
	
	public static function create()
	{
		$query = "INSERT INTO `twitter_message` (`active`, `created`) VALUES ('1', NOW())";
		$res = self::query($query);
		
		$query = "SELECT MAX(`id_twitter_message`) AS `val` FROM `twitter_message`";
		$res = self::query($query);
		
		if(mysql_num_rows($res) == '1')
		{
			$res = mysql_fetch_assoc($res);
			return new TwitterMessage($res['val']);
		}
		return false;
	}
	
	/**
	 * @todo Add content for the image icon
	 * @todo Add content for profile link
	 * @todo add content for creator
	 */
	public function createArrayForJSON()
	{
		$array['id'] = $this->getId();
		$array['id_service'] = $this->getService()->getId();
		$array['css_selector'] = $this->getService()->getCSSSelector();
		$array['image'] = '';
		$array['profile_link'] = 'http://twitter.com/'.$this->getTwitterProfileFrom()->getScreenName();
		$array['message'] = $this->getText();
		$array['created_at'] = $this->getCreatedAt();
		$array['author'] = '@'.$this->getTwitterProfileFrom()->getScreenName();
		foreach($this->getTagObjects() as $tag)
		{
			$array['applied_tags'][] = $tag->getTag();
		}
				
		foreach($this->getSearch()->getProject()->getTags() as $tag)
		{
			if(!$this->hasTag($tag))
			{
				$array['available_tags'][] = $tag;
			}
		}
		
		return $array;
	}
	
	public function decrimentSentiment($amt = 1)
	{
		$this->setSentiment($this->getSentiment() - $amt);
	}
	
	public function getCreatedAt()
	{
		return $this->queryForValue('created_at');
	}
	
	public function getSearch()
	{
		$query = "SELECT `id_search` AS `val` FROM `twitter_message` WHERE `id_twitter_message` = '".mysql_real_escape_string($this->getId())."'";
		
		$res = self::query($query);
		
		if(mysql_num_rows($res) >= 1)
		{
			$row = mysql_fetch_array($res);
			
			return new Search($row['val']);
		}
		return false;
	}
	
	public function getLatitude()
	{
		return $this->queryForValue('geo_lat');
	}
	
	public function getLongitude()
	{
		return $this->queryForValue('geo_long');
	}
	
	public function getSentiment()
	{
		return $this->queryForValue('sentiment');
	}
	
	public function getText()
	{
		return $this->queryForValue('text');
	}
	
	public function getService()
	{
		return new Service(1);
	}
	
	public function getTagObjects()
	{
		$query = "SELECT `id_tag` AS `val` FROM `tag` WHERE `id_message` = '".mysql_real_escape_string($this->getId())."' AND `id_service` = '".mysql_real_escape_string($this->getService()->getId())."' AND `active` = '1'";
		
		$res = self::query($query);
		
		$return_array = array();
		if(mysql_num_rows($res) > 0)
		{
			while($row = mysql_fetch_array($res))
			{
				$return_array[] = new Tag($row['val']);
			}
		}
		return $return_array;
	}
	
	public function getTwitterProfileFrom()
	{
		return new TwitterProfile($this->queryForValue('id_twitter_profile_from'));
	}
	
	public function getTwitterProfileTo()
	{
		return new TwitterProfile($this->queryForValue('id_twitter_profile_to'));
	}
	
	public function hasTag($tag)
	{
		$query = "SELECT `id_tag` AS `val` FROM `tag` WHERE `id_message` = '".mysql_real_escape_string($this->getId())."' AND `id_service` = '".mysql_real_escape_string($this->getService()->getId())."' AND `tag` = '".mysql_real_escape_string($tag)."' AND `active` = '1'";
		
		$res = self::query($query);
		
		if(mysql_num_rows($res) >= 1)
		{
			$row = mysql_fetch_array($res);
			
			return new Tag($row['val']);
		}
		return false;
	}
	
	public function incrimentSentiment($amt = 1)
	{
		$this->setSentiment($this->getSentiment() + $amt);
	}
	
	public function setCreatedAt($created_at)
	{
		return $this->updateValue('created_at', $created_at);
	}
	
	public function setGeo($geo)
	{
		return $this->updateValue('geo', $geo);
	}
	
	public function setISOLanguageCode($iso_language_code)
	{
		return $this->updateValue('iso_language_code', $iso_language_code);
	}
	
	public function setLatitude($geo_lat)
	{
		return $this->updateValue('geo_lat', $geo_lat);
	}
	
	public function setLatLongFromLocation($location)
	{
		$req = 'http://api.local.yahoo.com/MapsService/V1/geocode?appid=4tzVnu7V34F8Ail1nZ1f8ZimfSteqiB7aI7Z.9_28n.7w_kyE40AyuVHTuugcnYqD.0V0A--&output=php&location='.urlencode($location);
		
		$phpserialized = file_get_contents($req);
		$phparray = unserialize($phpserialized);
		
		$this->setPrecision($phparray['ResultSet']['Result']['precision']);
		if(is_float($lat = (float)$phparray['ResultSet']['Result']['Latitude']))
			$this->setLatitude($lat);
		if(is_float($long = (float)$phparray['ResultSet']['Result']['Longitude']))
			$this->setLongitude($long);
	}
	
	public function setLocation($location)
	{
		return $this->updateValue('location', $location);
	}
	
	public function setLocationSerial($location_serial)
	{
		return $this->updateValue('location_serial', $location_serial);
	}
	
	public function setLongitude($geo_long)
	{
		return $this->updateValue('geo_long', $geo_long);
	}
	
	public function setPrecision($precision)
	{
		return $this->updateValue('precision', $precision);
	}
	
	public function setResponseToTwitterMessage($response_to)
	{
		return $this->updateValue('id_twitter_message_response_to', $response_to->getId());
	}
	
	public function setSearchId($id_search)
	{
		return $this->updateValue('id_search', $id_search);
	}
	
	public function setSentiment($sentiment)
	{
		return $this->updateValue('sentiment', $sentiment);
	}
	
	public function setSource($source)
	{
		return $this->updateValue('source', $source);
	}
	
	public function setText($text)
	{
		return $this->updateValue('text', $text);
	}
	
	public function setTwitterMessageId($twitter_message_id)
	{
		return $this->updateValue('twitter_message_id', $twitter_message_id);
	}
	
	public function setTwitterProfileFrom($twitter_profile)
	{
		return $this->updateValue('id_twitter_profile_form', $twitter_profile->getId());
	}
	
	public function setTwitterProfileTo($twitter_profile)
	{
		return $this->updateValue('id_twitter_profile_to', $twitter_profile->getId());
	}
}
