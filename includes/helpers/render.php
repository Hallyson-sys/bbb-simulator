<?php

function render(string $arquivo, array $dados = [])
{
    extract($dados);

    require __DIR__ . '/../' . $arquivo . '.php';
}