<?php

namespace Neurolit\Tests ;

use Neurolit\EtherpadLite\Client ;

class EtherpadLitePHPClientTestCase extends \PHPUnit_Framework_TestCase{
  /**
   * @dataProvider etherpadConfs
   * @expectedException ErrorException
   * @expectedExceptionMessage Network Unreachable
   */
  public function testCreatePublicPadWithUnreachableNetwork($protocol,$server,$port,$apiKey,$suffix,$text){
    $responseStub = $this->getMock('Buzz\Message\Response',array('isOk','getReasonPhrase')) ;

    $responseStub->expects($this->any())
      ->method('isOk')
      ->will($this->returnValue(false)) ;
    
    $responseStub->expects($this->any())
      ->method('getReasonPhrase')
      ->will($this->returnValue('Network Unreachable')) ;

    $etherpad = new Client($protocol,
                           $server,
                           $port,
                           $apiKey) ;

    $browserMock = $this->getMock('Buzz\Browser',array('get')) ;

    $browserMock->expects($this->once())
      ->method('get')
      ->will($this->returnValue($responseStub));

    $etherpad->setBrowser($browserMock) ;

    $etherpad->createPad($suffix,$text) ;
  }

  /**
   * @dataProvider etherpadConfs
   * @expectedException ErrorException
   * @expectedExceptionMessage Horreur
   */
  public function testCreatePublicPadWithEtherpadError($protocol,$server,$port,$apiKey,$suffix,$text){
    // Les réponses sont NOK
    $responseStub = $this->_createResponseStub("1","Horreur");
    $this->_createPublicPad($protocol,$server,$port,$apiKey,$suffix,$text,$responseStub) ;
  }

  /**
   * @dataProvider etherpadConfs
   */
  public function testSetPassword($protocol,$server,$port,$apiKey,$suffix,$text){
    $padID = "Cesar";
    $password = "tuquoquemifili";
    $responseStub = $this->_createResponseStub("0","ok");
    $browserMock = $this->_createBrowserMock(array(
                                                   array(
                                                         $this->once(),
                                                         $this->stringStartsWith($protocol.'://'.$server.':'.$port.'/api/1.2.1/setPassword?apikey='.$apiKey.'&padID='.$padID.'&password='.$password),
                                                         $this->returnValue($responseStub)
                                                         )
                                                   )
                                             ) ;
    $etherpad = new Client($protocol,
                           $server,
                           $port,
                           $apiKey) ;
    $etherpad->setBrowser($browserMock) ;

    return $etherpad->setPassword($padID,$password);
  }

  /**
   * @dataProvider etherpadConfs
   */
  public function testCreatePublicPad($protocol,$server,$port,$apiKey,$suffix,$text){
    // Les réponses sont OK
    $responseStub = $this->_createResponseStub("0","ok");
    $this->assertRegexp("/^[a-zA-Z0-9]{16}(_$suffix)?$/",$this->_createPublicPad($protocol,$server,$port,$apiKey,$suffix,$text,$responseStub)) ;
  }

  /**
   * @dataProvider etherpadConfs
   */
  public function testCreateProtectedPad($protocol,$server,$port,$apiKey,$suffix,$text){
    // Les réponses sont OK
    $responseStub = $this->_createResponseStub("0","ok",'{"groupID":"g.9cPrs0P4ou9lKjad"}');

    $browserMock = $this->_createBrowserMock(array(
                                                   array(
                                                         $this->at(0),
                                                         $this->stringStartsWith($protocol.'://'.$server.':'.$port.'/api/1.2.1/createGroup?apikey='.$apiKey),
                                                         $this->returnValue($responseStub)),
                                                   array(
                                                         $this->at(1),
                                                         $this->logicalAnd(
                                                                           $this->stringStartsWith($protocol.'://'.$server.':'.$port.'/api/1.2.1/createGroupPad?apikey='.$apiKey.'&groupID=g.9cPrs0P4ou9lKjad&padName='),
                                                                           $this->stringEndsWith($suffix.'&text='.$text)),
                                                         $this->returnValue($responseStub)),
                                                   array(
                                                         $this->at(2),
                                                         $this->logicalAnd(
                                                                           $this->stringStartsWith($protocol.'://'.$server.':'.$port.'/api/1.2.1/setPublicStatus?apikey='.$apiKey.'&padID=g.9cPrs0P4ou9lKjad%24'),
                                                                           $this->stringEndsWith('&publicStatus=true')
                                                                           ),
                                                         $this->returnValue($responseStub)),
                                                   array(
                                                         $this->at(3),
                                                         $this->logicalAnd(
                                                                           $this->stringStartsWith($protocol.'://'.$server.':'.$port.'/api/1.2.1/setPassword?apikey='.$apiKey.'&padID=g.9cPrs0P4ou9lKjad%24'),
                                                                           $this->stringEndsWith('&password='."SuperPassword")
                                                                           ),
                                                         $this->returnValue($responseStub))
                                                   )
                                             ) ;
    
    $etherpad = new Client($protocol,
                           $server,
                           $port,
                           $apiKey) ;
    $etherpad->setBrowser($browserMock) ;

    $this->assertRegexp("/^g\.9cPrs0P4ou9lKjad\\\$[a-zA-Z0-9]{16}(_$suffix)?$/",$etherpad->createPad($suffix,$text,"SuperPassword")) ;

  }

  /**
   * @dataProvider etherpadConfs
   */
  public function testDeletePad($protocol,$server,$port,$apiKey,$suffix,$text){
    $padID = "Brutus";
    $responseStub = $this->_createResponseStub("0","ok");
    $browserMock = $this->_createBrowserMock(array(
                                                   array(
                                                         $this->once(),
                                                         $this->stringStartsWith($protocol.'://'.$server.':'.$port.'/api/1.2.1/deletePad?apikey='.$apiKey.'&padID='.$padID),
                                                         $this->returnValue($responseStub)
                                                         )
                                                   )
                                             ) ;

    $etherpad = new Client($protocol,
                           $server,
                           $port,
                           $apiKey) ;
    $etherpad->setBrowser($browserMock) ;

    return $etherpad->deletePad($padID) ;
  }

  /**
   * @dataProvider etherpadConfs
   */
  public function testGetText($protocol,$server,$port,$apiKey,$suffix,$text){
    $padID = "Brutus";
    $responseStub = $this->_createResponseStub("0","ok", '{"text": "tu quoque mi fili"}');
    $browserMock = $this->_createBrowserMock(array(
                                                   array(
                                                         $this->once(),
                                                         $this->stringStartsWith($protocol.'://'.$server.':'.$port.'/api/1.2.1/getText?apikey='.$apiKey.'&padID='.$padID),
                                                         $this->returnValue($responseStub)
                                                         )
                                                   )
                                             ) ;

    $etherpad = new Client($protocol,
                           $server,
                           $port,
                           $apiKey) ;
    $etherpad->setBrowser($browserMock) ;

    return $etherpad->getText($padID) ;
  }

  /**
   * @dataProvider etherpadConfs
   */
  public function testGetLastEdited($protocol,$server,$port,$apiKey,$suffix,$text){
    $padID = "Brutus";
    $responseStub = $this->_createResponseStub("0","ok", '{"lastEdited": "1340815946602"}');
    $browserMock = $this->_createBrowserMock(array(
                                                   array(
                                                         $this->once(),
                                                         $this->stringStartsWith($protocol.'://'.$server.':'.$port.'/api/1.2.1/getLastEdited?apikey='.$apiKey.'&padID='.$padID),
                                                         $this->returnValue($responseStub)
                                                         )
                                                   )
                                             ) ;

    $etherpad = new Client($protocol,
                           $server,
                           $port,
                           $apiKey) ;
    $etherpad->setBrowser($browserMock) ;

    return $etherpad->getLastEdited($padID) ;
  }

  /**
   * @dataProvider etherpadConfs
   */
  public function testListAllPads($protocol,$server,$port,$apiKey,$suffix,$text){
    $responseStub = $this->_createResponseStub("0","ok", '{"padIDs": ["firstPad", "secondPad"]}');
    $browserMock = $this->_createBrowserMock(array(
                                                   array(
                                                         $this->once(),
                                                         $this->stringStartsWith($protocol.'://'.$server.':'.$port.'/api/1.2.1/listAllPads?apikey='.$apiKey),
                                                         $this->returnValue($responseStub)
                                                         )
                                                   )
                                             );

    $etherpad = new Client($protocol,
                           $server,
                           $port,
                           $apiKey);
    $etherpad->setBrowser($browserMock);

    $pads = $etherpad->listAllPads();

    $this->assertEquals(2, sizeOf($pads));
    $this->assertEquals("firstPad",$pads[0]);
    $this->assertEquals($pads, array("firstPad","secondPad"));
  }

  public function etherpadConfs(){
    return array(
                 array('http','localhost','9001','apiKey1','suffixe','texte1'),
                 array('https','www.test.com','443','apiKey4','','texte5'),
                 array('https','www.test.com','443','apiKey4','',''),
                 array('https','www.test.com','443','apiKey4','suffixe',''),
                 );
  }

  /**
   * Creates a Stub for Etherpad Lite API Response
   * @param $code response code.
   * @param $message response message.
   * @param $data response data.
   */
  private function _createResponseStub($code,$message,$data="null"){
    $responseStub = $this->getMock('Buzz\Message\Response',array('isOk','getContent')) ;

    $responseStub->expects($this->any())
      ->method('isOk')
      ->will($this->returnValue(true)) ;

    $responseStub->expects($this->any())
      ->method('getContent')
      ->will($this->returnValue('{"code": '.$code.', "message":"'.$message.'", "data": '.$data.'}')) ;

    return $responseStub ;
  }

  /**
   * Creates a mock
   * @param array[] $constraints an array of arrays ; each array contains 3 constraits (expect_contraint, with_contraint, will_contraint)
   * @return object
   */
  private function _createBrowserMock($constraints){
    $browserMock = $this->getMock('Buzz\Browser',array('get')) ;

    foreach ($constraints as $constraint) {
      $browserMock->expects($constraint[0])
        ->method('get')
        ->with($constraint[1])
        ->will($constraint[2]);
    }

    return $browserMock ;
  }

  /**
   * Tests public pad creation
   **/
  private function _createPublicPad($protocol,$server,$port,$apiKey,$suffix,$text,$responseStub){
    // Browser should be called only once
    $browserMock = $this->_createBrowserMock(array(
                                                   array(
                                                         $this->once(),
                                                         $this->logicalAnd(
                                                                           $this->stringStartsWith($protocol.'://'.$server.':'.$port.'/api/1.2.1/createPad?apikey='.$apiKey.'&padID='),
                                                                           $this->stringEndsWith($suffix.'&text='.$text)),
                                                         $this->returnValue($responseStub)
                                                         )
                                                   )
                                             ) ;

    $etherpad = new Client($protocol,
                           $server,
                           $port,
                           $apiKey) ;
    $etherpad->setBrowser($browserMock) ;

    return $etherpad->createPad($suffix,$text) ;
  }

}