<?php

namespace MakinaCorpus\Calista\Tests\Mock;

class FooPropertyRenderer
{
    public function publicRenderFunction($value, array $options, $item)
    {
        return substr($value, 1, 5);
    }

    protected function protectedRenderFunction($value, array $options, $item)
    {
        throw new \RuntimeException("I shall not be called because I'm protected");
    }

    private function privateRenderFunction($value, array $options, $item)
    {
        throw new \RuntimeException("I shall not be called because I'm private");
    }
}
