<?php declare(strict_types = 1);

namespace Slate\Data {

    use DateInterval;
    use DateTimeInterface;
    use Slate\Data\Repository\IRepository;
    use Slate\Data\Repository\IRepositoryExpirable;

/**
     * The Cache boilerplate class.
     */
    abstract class Repository implements IRepository {
        protected bool      $autoforget;

        public function __construct(bool $autoforget = false) {
            $this->autoforget = $autoforget;
        }

    }
}

?>