<?
$dirs=DOC_INC."libraries/zend-gdata";
ini_set("include_path", ini_get('include_path').":{$dirs}");
require_once DOC_INC.'libraries/zend-gdata/Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_App_Exception');

class YouTubeData extends Data
{	
	public function __construct($id_youtube_account)
	{
		parent::__construct('youtubedata', $id_youtube_account);
	}
	
	
	public static function create()
	{
		$query = "INSERT INTO `youtubedata` (`active`, `created`) VALUES ('1', NOW())";
		$res = self::query($query);
		
		$query = "SELECT MAX(`id_youtubedata`) AS `val` FROM `youtubedata`";
		$res = self::query($query);
		
		if(mysql_num_rows($res) == '1')
		{
			$res = mysql_fetch_assoc($res);
			return new YouTubeData($res['val']);
		}
		return false;
	}
	
	public function getSlug()
	{
		return $this->queryForValue('slug');
	}
	
	public function getApiComments($videoId)	
	{
	  echo "Checking for comment on: {$videoId}";
	  $yt = new Zend_Gdata_YouTube();
	  // set the version to 2 to retrieve a version 2 feed
	  $yt->setMajorProtocolVersion(2);
	  $commentFeed = $yt->getVideoCommentFeed($videoId);
	  $count = 1;
	  foreach ($commentFeed as $commentEntry) 
	  {
	  	if (!CommentData::commentExists($commentEntry->id))
				{  
	  				echo 'YouTube Comment: ' . $commentEntry->title->text . "\n";
	  				$c=CommentData::create();
					$c->setMessageId($this->getId());
					$c->setCommentId($commentEntry->id);
					$c->setComment($commentEntry->content);
					$c->setAuthorName($commentEntry->author[0]->name);
					$c->setServiceId('3');
					$tZulu=$commentEntry->published;
					$toStrip=array("T", "Z");
					$tZuluStripped=str_replace($toStrip, " ", $tZulu);
					$tZuluStrippedUnix=strtotime($tZuluStripped);
					$gooddate=date('Y-m-d H:i:s', $tZuluStrippedUnix);		
					$c->setPermalink($commentEntry->id);
					$c->setDate($gooddate);
				}
			}
		}
	
	/*public function searchYouTube($query)
	{
	$youTubeService = new Zend_Gdata_YouTube();
    $query = $youTubeService->newVideoQuery();
    $query->setQuery($query);
    $query->setStartIndex($startIndex);
    $query->setMaxResults($maxResults);
    $feed = $youTubeService->getVideoFeed($query);        
    return $feed; 
	}
	//clean up + add updates
	public function getYouTubeDetails($feed)
	{
	foreach ($feed as $entry) {
	
	if(!$this->threadExistsYT($entry->getVideoId()))	
	{	//this goes through the thread
		$a=YouTubeData::create();
        $videoId = $entry->getVideoId();
        setVideoId($videoId);
        $thumbnailUrl = 'notfound.jpg';
        if (count($entry->mediaGroup->thumbnail) > 0) {
            $thumbnailUrl = htmlspecialchars(
                $entry->mediaGroup->thumbnail[0]->url);
        }
		setThumbnail($thumbnailUrl);
        $videoTitle = htmlspecialchars($entry->getVideoTitle());
        setTitle($videoTitle);
        $videoDescription = htmlspecialchars($entry->getVideoDescription());
        setDescription($videoDescription);
        $videoCategory = htmlspecialchars($entry->getVideoCategory());
        setCatagory($videoCatagory);
        $videoTags = $entry->getVideoTags();
		setTags($videoTags);
    	$authorUsername = htmlspecialchars($entry->author[0]->name);
    	setAuthorUsername($authorUsername);
   		$authorUrl = 'http://www.youtube.com/profile?user=' .
                 $authorUsername;
        setVideoAuthorUrl($authorUrl);         
    	/*$tags = htmlspecialchars(implode(', ', $entry->getVideoTags())); still have to figure out tage */
   		/*$duration = htmlspecialchars($entry->getVideoDuration());
   		setVideoDuration($duration);
    	$watchPage = htmlspecialchars($entry->getVideoWatchPageUrl());
    	setVideoWatchPage($watchPage);
    	$viewCount 	= htmlspecialchars($entry->getVideoViewCount());
    	setViewCount($viewCount);
    	$rating = 0;
    		if (isset($entry->rating->average)) {
       			 $rating = $entry->rating->average;
   				 }
   		 $numRaters = 0;
    		if (isset($entry->rating->numRaters)) {
       			 $numRaters = $entry->rating->numRaters;
    			}
		setVideoRating($rating);
		setVideoNumRaters($numRaters);
			}
	}
	} */
	
	//goes in main search class once we get there. 
	
	
	public static function getDataToCheckForComments()
	{
		$limit=50;
		$query = "SELECT `id_youtubedata` AS `val` FROM `youtubedata` WHERE `times_checked_comments`=0 OR (`times_checked_comments`=1 AND TIMESTAMPDIFF(MINUTE, `created`, `last_checked_comments`)>5) OR (times_checked_comments=2 AND TIMESTAMPDIFF(MINUTE, `created`, `last_checked_comments`)>15) OR (times_checked_comments=3 AND TIMESTAMPDIFF(MINUTE, `created`, `last_checked_comments`)>60) OR (`times_checked_comments`=4 AND TIMESTAMPDIFF(MINUTE, `created`, `last_checked_comments`)>480) LIMIT 0, ".mysql_real_escape_string($limit)."";
		$res = self::query($query);
		
		$return_array = array();
		
		if(mysql_num_rows($res) > 0)
		{
			while($row = mysql_fetch_array($res))
			{
				$return_array[] = new YouTubeData($row['val']);
			}
		}
		return $return_array;
	}
	
	public function searchForNewComments()
	{
	
		$curl=curl_init();
		$vidId=$this->getVideoId();
		$request="http://gdata.youtube.com/feeds/api/videos/{$vidId}/comments";
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_URL,$request);
		$response = curl_exec ($curl);
		curl_close($curl);
		try{	
			  	$commentFeed=new SimpleXMLElement($response);
			  	foreach ($commentFeed as $commentEntry) 
			  	{
			  		if (!CommentData::commentExists($commentEntry->id))
			  		{  
						echo 'YouTube Comment: ' . $commentEntry->title. "\n";
						$c=CommentData::create();
						$c->setMessageId($this->getId());
						$c->setCommentId($commentEntry->id);
						$c->setComment($commentEntry->content);
						$c->setAuthorName($commentEntry->author[0]->name);
						$c->setServiceId('3');
						$tZulu=$commentEntry->published;
						$toStrip=array("T", "Z");
						$tZuluStripped=str_replace($toStrip, " ", $tZulu);
						$tZuluStrippedUnix=strtotime($tZuluStripped);
						$gooddate=date('Y-m-d H:i:s', $tZuluStrippedUnix);		
						$c->setDate($gooddate);
					}
					$this->incrimentChecked();	
				}
		}
		Catch(Exception $e)
		{
			echo "Youtube had no response. Please try again later \n";
		}

	}
	
	public function incrimentChecked()
	{
		$this->incrimentLastChecked();
		return $this->updateValue('last_checked_comments', 'NOW()');
	}
	
	public function incrimentLastChecked()
	{
		return $this->updateValue('times_checked_comments', $this->queryForValue('times_checked_comments') + 1);
		
	}	
	
	
	
	
	
	public static function threadExistsYT($videoId)
	{
		$query = "SELECT * FROM `youtubedata` WHERE `video_id` = '".mysql_real_escape_string($videoId)."'";
		$res = self::query($query);
		if(mysql_num_rows($res) > 0)
		{
			return true;
		}
		return false;
	}
		
	public function getVideoId()
	{
		return $this->queryForValue('video_id');
	}
	
	public function setVideoId($videoID)
	{
		return $this->updateValue('video_id', $videoID);
	}	
	
	public function getCreatedAt()
	{
		return $this->queryForValue('updated');
	}
	
	public function getService()
	{
		return new Service(3);
	}	
	
	public function getMessage()
	{
		return $this->queryForValue('title');
	}
	
	public function setUpdated($updated)
	{
		return $this->updateValue('updated', $updated);
	}	
	
	public function setRecorded($recorded)
	{
		return $this->updateValue('recorded', $recorded);
	}	
	
	public function setSearchId($searchId)
	{
		return $this->updateValue('id_search', $searchId);
	}

	public function setThumbnail($thumb)
	{
		return $this->updateValue('thumbnail', $thumb);
	}
	
	public function setTitle($vidTitle)
	{
		return $this->updateValue('title', $vidTitle);
	}
		
	public function setDescription($desc)
	{
		return $this->updateValue('description', $desc);
	}
		
	public function setCatagory($catagory)
	{
		return $this->updateValue('catagory', $catagory);
	}
		
	public function setVideoTags($tags)
	{
		return $this->updateValue('video_tags', $tags);
	}
	
	public function setAuthorUsername($YTauthor)
	{
		return $this->updateValue('username', $YTauthor);
	}
	
	public function setVideoAuthorURL($videoAuthorUrl)
	{
		return $this->updateValue('author_url', $videoAuthorUrl);
	}
		
	public function setVideoDuration($videoDuration)
	{
		return $this->updateValue('video_duration', $videoDuration);
	}
	
	public function setVideoWatchPage($videoWatchUrl)
	{
		return $this->updateValue('web_url', $videoWatchUrl);
	}
	
	public function setViewCount($videoViews)
	{
		return $this->updateValue('view_count', $videoViews);
	}
		
	public function setVideoRating($videoRating)
	{
		return $this->updateValue('rating', $videoRating);
	}	
	
	public function setVideoNumRaters($videoNumRaters)
	{
		return $this->updateValue('num_raters', $videoNumRaters);
	}
	
	
		
	
//?	
	public function getThreads()
	{
		$query = "SELECT `id_thread` AS `val` FROM `thread` WHERE `active` = '1' AND ('0'='1'";
		foreach($this->getSearches() as $search)
		{
			$query .= " OR `id_search` = '".$search->getId()."'";
		}
		$query .= ") ORDER BY `message_time` DESC LIMIT 0,50";
		
		$res = self::query($query);
		
		$return_array = array();
		if(mysql_num_rows($res) > 0)
		{
			while($row = mysql_fetch_array($res))
			{
				$thread = new Thread($row['val']);
				//if($search->isActive())
				//{
					$return_array[] = $thread;
				//}
				//unset($search);
			}
		}
		return $return_array;
	}
	
	//?
	public function getThreadsForSelectedSearches($selected_searches)
	{
		$query = "SELECT `id_thread` AS `val` FROM `thread` WHERE `active` = '1' AND ('0'='1'";
		foreach($selected_searches as $search)
		{
			$query .= " OR `id_search` = '".$search->getId()."'";
		}
		$query .= ") ORDER BY `message_time` DESC LIMIT 0,50";
		
		$res = self::query($query);
		
		$return_array = array();
		if(mysql_num_rows($res) > 0)
		{
			while($row = mysql_fetch_array($res))
			{
				$thread = new Thread($row['val']);
				//if($search->isActive())
				//{
					$return_array[] = $thread;
				//}
				//unset($search);
			}
		}
		return $return_array;
	}
	

	//?
	public static function getYouTubeAccounts()
	{
		$query = "SELECT `id_youtube_account` AS `val` FROM `youtube_account` WHERE `active` = '1'";
		$res = self::query($query);
		
		$return_array = array();
		
		if(mysql_num_rows($res) > 0)
		{
			while($row = mysql_fetch_array($res))
			{
				$return_array[] = new YouTubeAccount($row['val']);
			}
		}
		return $return_array;
	}
	//?
	public static function getYouTubeAccountFromSlug($slug)
	{
		$query = "SELECT `id_youtube_account` AS `val` FROM `youtube_account` WHERE `slug` = '".mysql_real_escape_string($slug)."'";
		$res = self::query($query);
		
		$return_array = array();
		
		if(mysql_num_rows($res) == 1)
		{
			$row = mysql_fetch_array($res);
			return new YouTubeAccount($row['val']);
		}
		return false;
	}
	
	
	
	public function setSlug($slug)
	{
		return $this->updateValue('slug', $slug);
	}
	
	
}



?>
