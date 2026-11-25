<?php

namespace App\Services\Tqa;

use FPDF;

final class InformePdf extends FPDF
{
    private string $referencia = '';

    public function generar(array $informe): string
    {
        $this->referencia = $informe['id'];
        $this->SetTitle('Informe de pruebas · '.$informe['suite'], true);
        $this->SetAuthor('Sindicato de Transporte La Paz');
        $this->SetMargins(18, 20, 18);
        $this->SetAutoPageBreak(true, 20);
        $this->AliasNbPages();
        $this->AddPage();

        $this->texto('Informe de pruebas', 23, 'B', 10);
        $this->texto($informe['tipo'].'  /  '.$informe['suite'], 14, 'B', 7);
        $this->texto($informe['descripcion_suite'], 10, '', 5);
        $this->Ln(4);
        $this->SetFillColor(227, 239, 245);
        $this->SetFont('Helvetica', 'B', 11);
        $this->Cell(0, 10, $this->codificar('  '.($informe['estado'] === 'finalizada' ? 'Finalizada' : 'Con incidencias').'   |   '.$informe['resumen']['total'].' de '.$informe['seleccionados'].' casos con resultado'), 0, 1, 'L', true);
        $this->Ln(2);
        $resumen = $informe['resumen'];
        $this->texto("PASS   {$resumen['pass']}      FAIL   {$resumen['fail']}      SKIPPED   {$resumen['skipped']}", 11, 'B', 6);
        $this->texto('Aprobación sobre casos con resultado: '.($resumen['total'] ? $resumen['exito'].'%' : 'sin datos').'     |     Aserciones registradas: '.array_sum(array_column($informe['casos'], 'aserciones')), 10);
        $this->Ln(3);
        $this->texto('Inicio: '.$informe['inicio'].'     Fin: '.$informe['fin'].'     (La Paz)', 10);
        $this->texto('Duración: '.$informe['duracion'].'     |     Entorno: testing     |     Ejecutor: PHPUnit', 10);
        $this->texto('Destinatario: '.$informe['correo'], 10);
        if ($informe['error_proceso']) {
            $this->Ln(2);
            $this->texto('Incidencia de ejecución', 11, 'B');
            $this->texto($informe['error_proceso']);
        }
        foreach ($informe['sin_resultado'] as $caso) {
            $this->texto('Sin resultado registrado: '.$caso['descripcion']);
        }
        if ($informe['casos']) {
            $this->reservar(56);
            $this->Ln(5);
            $this->texto('Detalle por caso', 16, 'B', 8);
            foreach ($informe['casos'] as $caso) {
                $this->caso($caso);
            }
        }

        return $this->Output('S');
    }

    public function Header(): void
    {
        $this->SetFillColor(21, 51, 72);
        $this->Rect(0, 0, 210, 15, 'F');
        $this->SetXY(18, 4);
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 7, 'CENTRO TQA  /  Sindicato de Transporte La Paz');
        $this->SetXY(18, 22);
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetDrawColor(197, 212, 221);
        $this->Line(18, $this->GetY(), 192, $this->GetY());
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(72, 89, 105);
        $this->Cell(139, 8, $this->codificar('Ejecución  '.$this->referencia));
        $this->Cell(35, 8, $this->codificar('Página '.$this->PageNo().' de {nb}'), 0, 0, 'R');
    }

    private function caso(array $caso): void
    {
        $this->reservar(45);
        $this->Ln(3);
        $this->SetFillColor(...match ($caso['estado']) {
            'PASS' => [223, 241, 230],
            'FAIL' => [253, 228, 228],
            default => [250, 241, 213],
        });
        $this->SetTextColor(27, 47, 61);
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(0, 8, '  '.$caso['id'].'     '.$caso['estado'].'     |     '.$caso['duracion'].'     |     Aserciones: '.$caso['aserciones'], 0, 1, 'L', true);
        $this->Ln(2);
        $this->texto($caso['comprobacion'] ?? $caso['nombre'], 11, 'B', 5.5);
        $this->texto('Método: '.$caso['clave'], 9);
        if ($caso['estado'] === 'FAIL') {
            $this->texto('Error registrado por PHPUnit', 10, 'B');
            $this->texto($caso['error'] ?: $caso['observacion'], 9);
        } elseif ($caso['estado'] === 'SKIPPED') {
            $this->texto('Motivo de omisión: '.$caso['observacion']);
        }
    }

    private function reservar(float $alto): void
    {
        if ($this->GetY() + $alto > 277) {
            $this->AddPage();
        }
    }

    private function texto(string $texto, float $tamano = 10, string $estilo = '', float $alto = 4.8): void
    {
        $this->SetTextColor(27, 47, 61);
        $this->SetFont('Helvetica', $estilo, $tamano);
        $this->MultiCell(0, $alto, $this->codificar($texto), 0, 'L');
    }

    private function codificar(string $texto): string
    {
        return mb_convert_encoding(str_replace("\t", '    ', $texto), 'Windows-1252', 'UTF-8');
    }
}
