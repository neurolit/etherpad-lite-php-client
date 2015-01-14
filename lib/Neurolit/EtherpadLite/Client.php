<?php
namespace Neurolit\EtherpadLite;

use Buzz\Browser;

class Client {
  const API_VERSION = '1.2.1';

  private $server ;
  private $port ;
  private $apiKey ;
  private $protocol ;
  private $apiUrl ;
  private $browser ;

  public function __construct($protocol,$server,$port,$apiKey,$browser=null){
    $this->server = $server ;
    $this->port = $port ;
    $this->apiKey = $apiKey ;
    $this->protocol = $protocol ;
    $this->apiUrl = $protocol."://".$server.":".$port."/api/".self::API_VERSION."/" ;
    $this->browser = $browser ? $browser : new Browser() ;
  }

  /**
   * @codeCoverageIgnore
   */
  public function getPort(){
    return $this->port ;
  }

  /**
   * @codeCoverageIgnore
   */
  public function getServer(){
    return $this->server ;
  }

  /**
   * @codeCoverageIgnore
   */
  public function getProtocol(){
    return $this->protocol ;
  }

  /**
   * @codeCoverageIgnore
   */
  public function getApiKey(){
    return $this->apiKey ;
  }

  public function setBrowser($browser){
    $this->browser = $browser ;
  }

  public function createPad($suffix="",$text="",$password="") {
    if ("" === $password) {
      return $this->createPublicPad($suffix,$text) ;
    } else {
      return $this->createProtectedPad($password,$suffix,$text) ;
    }
  }

  public function setPassword($padID,$password) {
    $this->execAction('setPassword', array("padID" => $padID,"password" => $password)) ;
  }

  public function getText($padID){
    $jsonResponse = $this->execAction('getText', array("padID" => $padID)) ;
    return $jsonResponse->{'data'}->{'text'};
  }

  public function getLastEdited($padID){
    $jsonResponse = $this->execAction('getLastEdited', array("padID" => $padID)) ;
    return $jsonResponse->{'data'}->{'lastEdited'};
  }

  public function listAllPads(){
    $jsonResponse = $this->execAction('listAllPads');
    return $jsonResponse->{'data'}->{'padIDs'};
  }

  public function deletePad($padID) {
    $this->execAction('deletePad', array("padID" => $padID)) ;
  } 

  private function createProtectedPad($password, $suffix, $text=""){
    $padId = $this->generatePadId($suffix) ;

    $jsonResponse = $this->execAction('createGroup') ;
    $group = $jsonResponse->{'data'}->{'groupID'} ;

    $this->execAction("createGroupPad",
                      array ("groupID" => $group,
                             "padName" => $padId,
                             "text"    => $text));

    $this->execAction("setPublicStatus",
                      array("padID" => "$group\$$padId",
                            "publicStatus" => "true")) ;

    $this->execAction("setPassword",
                      array("padID" => "$group\$$padId",
                            "password" => $password));

    return "$group\$$padId";
  }

  private function createPublicPad($suffix, $text=""){
    $padId = $this->generatePadId($suffix) ;
    $this->execAction("createPad",
                      array("padID" => $padId,
                            "text"  => $text)) ;

    return "$padId" ;
  }

  private function execAction($action,$parameters=array()){
    $response = $this->browser->get($this->createActionUrl($action,$parameters)) ;

    if ($response->isOk()) {
        $json = json_decode($response->getContent());

        if ($json->{'code'} != 0) {
            throw new \ErrorException($json->{'message'});
        }
        return $json;
    } else {
        throw new \ErrorException($response->getReasonPhrase()) ;
    }

  }

  private function createActionUrl($action,$parameters=array()){
    $actionUrl = $this->apiUrl.urlencode($action);
    $actionUrl .= "?apikey=".$this->apiKey;
    foreach ($parameters as $paramName => $paramValue) {
        $actionUrl .= "&".urlencode($paramName)."=".urlencode($paramValue) ;
    }
    return $actionUrl ;
  }

  private function generatePadId($suffix){
    $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    $string_length = 16;
    $randomstring = "";
    for ($i = 0; $i < $string_length; $i++) {
      $randomstring .= $chars[rand()%strlen($chars)];
    }
    if ($suffix) {
      $randomstring .= "_" . $suffix;
    }
    return $randomstring;
  }
}
?>
