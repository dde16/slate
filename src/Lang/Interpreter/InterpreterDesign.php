<?php

namespace Slate\Lang\Interpreter {
    use ArrayAccess;

    
    use Slate\Lang\Interpreter\Attribute\Token as TokenAttribute;
    use Slate\Lang\Interpreter\Attribute\LiteralToken as TokenLiteralAttribute;
    use Slate\Lang\Interpreter\Attribute\RangeToken as TokenCharRangeAttribute;
    use Slate\Lang\Interpreter\Attribute\CompoundToken as TokenCompoundAttribute;
    use Slate\Lang\Interpreter\Attribute\ComplexToken as TokenComplexConstantAttribute;
    use Slate\Lang\Interpreter\Attribute\ComplexTokeniser as TokenComplexMethodAttribute;
    use Slate\Metalang\MetalangDesign;

class InterpreterDesign extends MetalangDesign  {
        public array  $tokens = [];

        public int    $ignoring              = 0;
        public int    $tracking             = 0;
        public int    $countTracked         = 0;
        public array  $countTrackedMap      = [];
        public int    $levelTrackedOpen       = 0;
        public int    $levelTrackedClose      = 0;

        public array  $levelTrackedMap        = [];
        public array  $levelTrackedOpenMap    = [];
        public array  $levelTrackedCloseMap   = [];

        public int    $levelTrackedTable            = 0;
        public array  $levelTrackedTableMap        = [];

        public int    $levelTrackedResetOpen       = 0;
        public int    $levelTrackedResetClose      = 0;

        public array  $levelTrackedResetMap        = [];
        public array  $levelTrackedResetOpenMap    = [];
        public array  $levelTrackedResetCloseMap   = [];


        public function __construct(string $class) {
            parent::__construct($class);

            if($tokens = \Cls::getConstant($class, "PRIORITY")) {
                foreach($tokens as $id) {
                    if($token = $this->getAttrInstance(TokenAttribute::class, $id)) {
                        $this->tokens[$id] = &$this->customAttributeInstances[$token::class][$id];
                    }
                }
            }
            else {
                foreach($this->getAttrInstances(TokenAttribute::class) as $token) {
                    $this->tokens[$token->parent->getValue()] = $token;
                }
            }
            

            foreach($this->getAttrInstances(TokenComplexConstantAttribute::class) as $attribute) {
                $implementor = $this->getAttrInstance(
                    TokenComplexMethodAttribute::class,
                    $attribute->parent->getValue()
                );

                if($implementor === null) {
                    throw new \Error(\Str::format(
                        "Token {}::{}(v={}) must have an implementor.",
                        $this->getName(),
                        $attribute->parent->getName(),
                        $attribute->parent->getValue()
                    ));
                }

                $attribute->implementor = $implementor;
            }

            foreach((\Cls::getConstant($class, "TRACKCOUNT") ?: []) as $alias => $tokens) {
                if(!is_array($tokens))
                    $tokens = [$tokens];

                foreach($tokens as $tokenId) {
                    if(\Integer::hasBits($this->countTracked, $tokenId))
                        throw new \Error(\Str::format("Already counting token '{}'.", $tokenName));

                    if(!\Arr::contains($this->countTrackedMap, $alias)) {
                        $trackId = 1 << $this->tracking;
                        $this->countTrackedMap[$trackId]       = $alias;
                    }
                    else {
                        $trackId = \Arr::find($this->countTrackedMap, $alias);
                    }
                    
                    $this->countTracked                   ^= $tokenId;
                    $this->countTrackedTokenMap[$tokenId]  = $trackId;

                    $this->tracking++;
                }
            }

            foreach((\Cls::getConstant($class, "TRACKLEVEL") ?: []) as $alias => $tokens) {
                list($openTokens, $closeTokens) = $tokens;

                if(!is_array($openTokens))
                    $openTokens = [$openTokens];
                
                if(!is_array($closeTokens))
                    $closeTokens = [$closeTokens];

                $trackId = 1 << $this->tracking;
                $this->levelTrackedMap[$trackId] = $alias;
    
                foreach($openTokens as $openTokenId) {
                    $this->levelTrackedOpen ^= $openTokenId;
                    $this->levelTrackedOpenMap[$openTokenId][]   = $trackId;
                }
    
                foreach($closeTokens as $closeTokenId) {
                    $this->levelTrackedClose ^= $closeTokenId;
                    $this->levelTrackedCloseMap[$closeTokenId][]   = $trackId;
                }
                
                $this->tracking++;
            }

            foreach((\Cls::getConstant($class, "TRACKRESET") ?: []) as $alias => $tokens) {
                list($openTokens, $closeTokens) = $tokens;

                if(!is_array($openTokens))
                    $openTokens = [$openTokens];
                
                if(!is_array($closeTokens))
                    $closeTokens = [$closeTokens];

                $trackId = 1 << $this->tracking;
                $this->levelTrackedResetMap[$trackId] = $alias;
    
                foreach($openTokens as $openTokenId) {
                    $this->levelTrackedResetOpen ^= $openTokenId;
                    $this->levelTrackedResetOpenMap[$openTokenId][]   = $trackId;
                }
    
                foreach($closeTokens as $closeTokenId) {
                    $this->levelTrackedResetClose ^= $closeTokenId;
                    $this->levelTrackedResetCloseMap[$closeTokenId][]   = $trackId;
                }
                
                $this->tracking++;
            }

            foreach((\Cls::getConstant($class, "TRACKTABLE") ?: []) as $alias => $tokenId) {
                $this->levelTrackedTableMap[$tokenId] = $alias;
                $this->levelTrackedTable ^= $tokenId;
            }

            foreach((\Cls::getConstant($class, "IGNORE") ?: []) as $tokenId) {
                $this->ignoring ^= $tokenId;
            }
        }
    }
}

?>