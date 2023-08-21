<?php

namespace App\Dtos;

use Carbon\Carbon;

class InserePessoaDto
{
    public string $nome;

    public string $apelido;

    private string $nascimento;

    public array $stack;

    public function __set(string $name, $value): void
    {
        if ($name == 'nascimento') {
            $this->nascimento = Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
        }
    }

    public function __get(string $name)
    {
        return $this->{$name};
    }


}
