<?php declare(strict_types = 1);

namespace Slate\Exception {
    use Slate\Http\HttpCode;
    use Exception;
    
    class HttpException extends Exception {
        public int     $httpCode    = 500;
        public ?string $httpMessage = null;

        public function __construct(int $httpCode, string $message = null) {

            if($message === null)
                $message = "No detail given.";
            
            parent::__construct($message);

            $this->httpCode    = $httpCode;
            $this->httpMessage = HttpCode::message($httpCode);
        }
    }
}


?>