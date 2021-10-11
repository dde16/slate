<?php

namespace Slate\Sql {
    trait TSqlModifiers {
        public int $modifiersTrue  = 0;
        public int $modifiersFalse = 0;
    
        public function buildModifier(int $modifier): ?string {
            $modifierDefinition = SqlModifier::DEFINITIONS[$modifier];
            $modifierHasOptions = is_array($modifierDefinition);
            $modifierBuild      = null;
            $modifierTrue       = $this->modifiersTrue & $modifier;
    
            if($modifierHasOptions) {
                if($modifierTrue || boolval($this->modifiersFalse & $modifier)) {
                    $modifierBuild = [$modifierDefinition[0], $modifierDefinition[1][intval(boolval($modifierTrue))]];
                }
            }
            else if($modifierTrue) {
                $modifierBuild = [$modifierDefinition];
            }
    
            return $modifierBuild ? \Arr::join(\Arr::filter($modifierBuild), " ") : null;
        }

        public function buildModifiers(array $modifiers): array {
            return \Arr::map($modifiers, fn($modifier) => $this->buildModifier($modifier));
        }
    
        public function setModifier(int $modifier, bool $to = true): static {
            if(static::MODIFIERS & $modifier) {
                $offModifierStore = $to ? 'modifiersFalse' : 'modifiersTrue';
                $onModifierStore = $to ? 'modifiersTrue' : 'modifiersFalse';
    
                if(!\Integer::hasBits($this->{$onModifierStore}, $modifier))
                    $this->{$onModifierStore} |= $modifier;
    
                if(\Integer::hasBits($this->{$offModifierStore}, $modifier))
                    $this->{$offModifierStore} &= ~$modifier;
            }
    
            return $this;
        }
    }
}

?>