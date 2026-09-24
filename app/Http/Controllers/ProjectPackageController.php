<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProductionPackageService;
use Illuminate\Http\Response;
use Throwable;

class ProjectPackageController extends Controller
{
    public function download(
        Project $project,
        ProductionPackageService $packageService
    ): Response {
        try {
            $zipPath = $packageService->create($project);

            return response()
                ->download(
                    $zipPath,
                    basename($zipPath),
                    [
                        'Content-Type' => 'application/zip',
                    ]
                )
                ->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            report($e);

            abort(
                500,
                'No se pudo generar el paquete de producción: '
                . $e->getMessage()
            );
        }
    }
}