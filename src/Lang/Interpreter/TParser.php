<?php

namespace Slate\Lang\Interpreter {

    use Closure;
    use Slate\Lang\Interpreter\Tokeniser\TokenMatch;
    use Slate\Lang\Interpreter\Attribute\TokenAttribute;
    use Slate\Data\Iterator\BufferedIterator;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;

    use Generator;

    trait TParser {
        public BufferedIterator $tokenMatches;

        public abstract function parse(): Generator;

        protected function expectWrapped(Closure $between, string|int $wrapperKey, bool $raise = false) {
            if($leftToken = $this->expectToken($wrapperKey)) {
                $betweenToken = $between();

                if($rightToken = $this->expectToken($wrapperKey, $raise)) {
                    return $betweenToken;
                }
                else if($raise) {
                    throw new \Error(\Str::format("Unclosed group near {}.", $leftToken->counters["pointer"]));
                }

                $this->pointer--;
            }
        }

        protected function expectList(Closure $between, int $delimiter, bool $raise = false, bool $nonEmpty = false): Generator {
            if(($betweenInitialToken = $between()) !== null) {
                yield $betweenInitialToken;

                while(($delimiterToken = $this->expectToken($delimiter, raise: $raise)) !== null) {
                    if($betweenIntermediateToken = $between()) {
                        yield $betweenIntermediateToken;
                    }
                    else {
                        throw new \Error("Expecting item after the delimiter.");
                    }
                }
            }
            else if($nonEmpty) {
                throw new \Error("List must have an initial item.");
            }
        }

        public function expectZeroPlus(int $key): Generator {
            if(($initialToken = $this->expectToken($key)) !== null) {
                yield $initialToken;

                while(($nextToken = $this->expectToken($key)) !== null) {
                    yield $nextToken;
                }
            }
        }

        public function expectOnePlus(int $key): Generator {
            if(($initialToken = $this->expectToken($key, raise: true)) !== null) {
                yield $initialToken;

                while(($nextToken = $this->expectToken($key)) !== null) {
                    yield $nextToken;
                }
            }
        }

        public function expectToken(int $key, bool $raise = false, bool $raiseEof = false): mixed {
            if(($token = $this->tokenMatches->current()) !== null) {
                if($token->id === $key) {
                    $this->tokenMatches->next();
                    return $token;
                }
                else if($raise) {
                    throw new \Error(\Str::format(
                        "Unexpected token '{}' (type {}) at position {}, expecting {}",
                        \Str::swap($token->getValue($this->code), [ "\n" => "\\n", "\r" => "\\r" ]),
                        \Str::title(\Str::lower($token->name)),
                        $token->counters["pointer"],
                        \Str::title(\Str::lower(static::design()->tokens[$key]->parent->getName())),
                    ));
                }
            }
            else if($raiseEof) {
                throw new \Error(
                    "Unexpected EOF"
                );
            }

            return null;
        }
    }
}

?>