<?php

namespace App\Modelo;

class Servicio
{
    public function __construct(
        private ?int $idServicio,
        private int $idCategoria,
        private string $nombreServicio,
        private int $precioMedioEstimado,
        private float $tiempoMedioEstimado,
        private ?string $descripcion = null
    ) {}

    //getters y setters
    public function getIdServicio(): ?int
    {
        return $this->idServicio;
    }
 
    public function getIdCategoria(): int
    {
        return $this->idCategoria;
    }
 
    public function getNombreServicio(): string
    {
        return $this->nombreServicio;
    }
 
    public function getPrecioMedioEstimado(): int
    {
        return $this->precioMedioEstimado;
    }
 
    public function getTiempoMedioEstimado(): float
    {
        return $this->tiempoMedioEstimado;
    }
 
    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }
 
    public function setIdServicio(?int $idServicio): void
    {
        $this->idServicio = $idServicio;
    }
 
    public function setIdCategoria(int $idCategoria): void
    {
        $this->idCategoria = $idCategoria;
    }
 
    public function setNombreServicio(string $nombreServicio): void
    {
        $this->nombreServicio = $nombreServicio;
    }
 
    public function setPrecioMedioEstimado(int $precioMedioEstimado): void
    {
        $this->precioMedioEstimado = $precioMedioEstimado;
    }
 
    public function setTiempoMedioEstimado(float $tiempoMedioEstimado): void
    {
        $this->tiempoMedioEstimado = $tiempoMedioEstimado;
    }
 
    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }
}
