<?php
  class Youtube {
    var $url = "https://www.googleapis.com/youtube/v3/";
    
    function getList($resource, $arg) {
      $query = "";
      foreach($arg as $key => $value) {
        $query .= "&".$key."=".$value;
      }
      $query = $this->url.$resource."?".substr($query, 1);
      return json_decode(file_get_contents($query));
    }
  }

  if(isset($_POST['submit'])) {
    $apiKey = $_POST['apikey'];
    $userName = $_POST['username'];

    function user_video_list($userName, $apiKey) {
      if(!is_string($userName))
        return "User name is not a string";
      elseif(!is_string($apiKey))
        return "API key is not a string";
      elseif(empty($userName))
        return "User name is empty";
      elseif(empty($apiKey))
        return "API key is empty";

      $youtube = new Youtube();
      $htmlBodyHeader = "<h1>Video of user <a href=\"https://www.youtube.com/user/".$userName."\">";
      $htmlBody = "</a></h1><ol>";
      $userNameDisplay = NULL;

      $channelPageToken = "";
      while(isset($channelPageToken)) {
        $channelList = $youtube->getList("channels", array('part' => 'contentDetails,snippet',
                                                           'pageToken' => $channelPageToken,
                                                           'forUsername' => $userName,
                                                           'key' => $apiKey));
        $channelPageToken = (property_exists($channelList, "nextPageToken")?$channelList->nextPageToken:NULL);
        foreach($channelList->items as $channel) {
          $playlistId = $channel->contentDetails->relatedPlaylists->uploads;
          $videoPageToken = "";
          $userNameDisplay = $channel->snippet->title;
          while(isset($videoPageToken)) {
            $videoList = $youtube->getList("playlistItems", array('part' => 'snippet',
                                                                  'maxResult' => '50',
                                                                  'pageToken' => $videoPageToken,
                                                                  'playlistId' => $playlistId,
                                                                  'key' => $apiKey));
            $videoPageToken = (property_exists($videoList, "nextPageToken")?$videoList->nextPageToken:NULL);
            foreach($videoList->items as $video) {
              $htmlBody .= sprintf("<li><a href=\"https://www.youtube.com/watch?v=%s\">%s</a></li>", $video->snippet->resourceId->videoId, $video->snippet->title);
            }
          }
        }
      }
      if(!isset($userNameDisplay))
        $userNameDisplay = $userName;
      $htmlBody .= "</ol>";
      return $htmlBodyHeader.$userNameDisplay.$htmlBody;
    }

    $htmlBody = user_video_list($userName, $apiKey);
  }
?>

<!doctype html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>List of user's youtube video</title>
  </head>
  <body>
    <form name="videolist" action="<?php $_SERVER['PHP_SELF'] ?>" method="POST">
      <label>User name: <input name="username" type="text" size="40" value="<?php if(isset($_POST['username'])) echo $_POST['username']; ?>" /></label>
      <label>API key: <input name="apikey" type="text" size="40" value="<?php if(isset($_POST['apikey'])) echo $_POST['apikey']; ?>" /></label>
      <input name="submit" type="submit" value="Get video list" />
    </form>
    <?php if(isset($htmlBody)) echo $htmlBody; ?>
  </body>
</html>