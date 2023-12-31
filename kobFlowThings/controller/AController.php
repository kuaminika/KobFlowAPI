<?php


namespace kobFlowThings\controller;
use K_Utilities\KMessageCodeMap;
use K_Utilities\KErrorCodeMap;
use K_Utilities\KError;

abstract class AController
{
    protected $service;
    protected $logTool;
    private $requestAction;
    private $params;
    protected $response;
    private $kTokenFacade;
    protected $otherConfigs;

    protected $messageMap;  

    public function __construct(ControllerToolBox $toolbox)
    {
      $this->messageMap = $toolbox->getMessageMap();
      $this->requestAction = $toolbox->getRequestAction();
      $this->requestMethod = $toolbox->getRequestMethod();
      $this->params = $toolbox->getRequestParams();
      $this->response = ["status_code_header"=>"HTTP/1.1 200 OK",
                        "body"=>json_encode([])
                        ];
      $this->otherConfigs = $toolbox->getOtherConfigs();
    
    }


    public function index()
    {
      $result = "index";
      $this->response['status_code_header'] = 'HTTP/1.1 200 OK';
      $this->response['body'] = $result;
    }

    public function findAll()
    {
      $result = $this->service->findAll();
      $this->response['status_code_header'] = 'HTTP/1.1 200 OK';
      $this->response['body'] = $result->_toJson();
    }


    protected function notFound()
    {
      
      $this->response['status_code_header'] = "HTTP/1.1 404  Not Found";
      header($this->response['status_code_header']);
      $this->response["body"] ="Action is not Found";
    }

    protected function logAndSend($errorCode,$location,$errorMessage=null)
    {
        $location = get_class($this).$location;
        $errorMessage = isset($errorMessage)? $errorMessage : KErrorCodeMap::errorCodeDescription($errorCode);
        $error = new KError($errorMessage,$location,$errorCode);
      
       // $this->logTool->log($error->_toJson());

        $this->response['status_code_header'] = 'HTTP/1.1 500 ERROR';
        $this->response['body'] = json_encode($error);


    }

    protected function handleError($errorMessage)
    { 
      $error = $errorMessage;

      $this->response['status_code_header'] = 'HTTP/1.1 500 ERROR';
      $this->response['body'] = json_encode($error);
      
      
      $this->logAndSend("exception","processRequest",$error);

     header($this->response['status_code_header']);
      echo $this->response['body'];
      exit;

    }

    public function processRequest()
    {
        try 
        {
          $params = $this->params;
          $itsFound = method_exists($this, $this->requestAction);
            if(!$itsFound)
            {

                 $this->notFound();
                 echo  $this->response['body'];
                 return;
            }

          call_user_func_array(array($this, $this->requestAction), [$params]);
  
          header($this->response['status_code_header']);
          if ($this->response['body']) 
          {
              echo $this->response['body'];
          }
        } 
        catch(\K_Utilities\KException $ex)
        {
           $error =  $ex->getErrorModel();  
           $this->response['status_code_header'] = 'HTTP/1.1 500 ERROR';
           $this->response['body'] = json_encode($error);
           
          header($this->response['status_code_header']);
           echo $this->response['body'];
           exit;
        }
        catch (\Throwable $th) 
        {
          $this->logAndSend("exception","processRequest",$th->getMessage());
          //throw $th;
        }
    }



}