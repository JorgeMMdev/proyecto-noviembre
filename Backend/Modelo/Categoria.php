<?php

namespace App\Modelo;

class Categoria {

    public function __construct(
        private ?int $idCategoria,
        private string $nombreCategoria,
        private ?string $descripcion = null
    ) {

    }

    //getters y setters

     public function getIdCategoria(): ?int
    {
        return $this->idCategoria;
    }
 
    public function getNombreCategoria(): string
    {
        return $this->nombreCategoria;
    }
 
    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }
 
    public function setIdCategoria(?int $idCategoria): void
    {
        $this->idCategoria = $idCategoria;
    }
 
    public function setNombreCategoria(string $nombreCategoria): void
    {
        $this->nombreCategoria = $nombreCategoria;
    }
 
    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }
}