<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class asistenciaController{
    private $asistenciaModel;

    public function __construct($asistenciaModel)
    {
        $this->asistenciaModel= $asistenciaModel;
    }

    public function index(){
        require_once __DIR__ .'/../views/index.php';
    }

/** 
 * 
 * 
* @param array|null $file
* @return array
*/
    public function validarSubidaExcel($file){
        if(!$file || empty($file['name'])){
           return[
            'valido' => false,
            'mensaje' => 'no se a selecionado ningun archivo'
           ];
        }
        if($file['error'] != UPLOAD_ERR_OK){
            return[
                'valido' => false,
                'mensaje' => 'Error al subir el archivo. Error='. $file['error']
            ];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $extensionesPermitidas = ['xlsx', 'xls', 'csv'];

        if(!in_array($extension, $extensionesPermitidas)){
            return[
                'valido' => false,
                'mensaje' => 'Formato incorrecto. Por farvor subir un formato que sea .xlsx, .xls o .csv.'
            ];
        }
        return[
            'valido' => true,
            'mensaje' => 'archivo valido'
        ];
    }

    public function procesarExcel(){
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('location: index.php?controllers=asistencia&accion=index');
            exit;
        }
            $file= $_FILES['archivo_excel']?? null;
            $validacion= $this->validarSubidaExcel($file);
            
            if(!$validacion['valido']){
                $_SESSION['alerta']= [
                    'tipo' => 'danger',
                    'mensaje' => $validacion['mensaje']
                ];
                header('location: index.php?controllers=asistencia&accion=index');
                exit;
            }
            try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);
            $cabezera= true;
            $asistenciasValidas= [];
            $errores= [];

            foreach ($spreadsheet->getAllSheets() as $hoja) {
                $nombreHoja = $hoja->getTitle();

                // Ignoramos las hojas informativas o de resumen
                if (in_array($nombreHoja, [
                    'Información de turnos',
                    'Hoja de resumen de asistencia',
                    'Registros de asistencia',
                    'Estadísticas de anormalías'
                ])) {
                    continue;
                }
                $worksheet = $hoja;
                $nombre1= $worksheet->getCell('J3')->getValue();
                $id1= $worksheet->getCell('J4')->getValue();

                $nombre2= $worksheet->getCell('Y3')->getValue();
                $id2= $worksheet->getCell('Y4')->getValue();

                $nombre3= $worksheet->getCell('AN3')->getValue();
                $id3= $worksheet->getCell('AN4')->getValue();

                $asistenciaEmpleado1=[];
                $asistenciaEmpleado2=[];
                $asistenciaEmpleado3=[];

                for($file= 12; $file <= 42; $file++){
                    $diaMes = $file - 11;
                    if(!empty($id1)){
                        $entrada1= $worksheet->getCell('K'. $file)->getValue();
                        $salida1= $worksheet->getCell('N'. $file)->getValue();
                        
                        if(!empty($entrada1) || !empty($salida1)){
                            $asistenciasValidas[]= [
                                'id_personal'=> $id1,
                                'dia'=> $diaMes,
                                'entrada'=> $entrada1,
                                'salida'=> $salida1
                            ];
                        }
                    }
                    if(!empty($id2)){
                        $entrada2= $worksheet->getCell('Z'. $file)->getValue();
                        $salida2= $worksheet->getCell('AB'. $file)->getValue();
                        
                        if(!empty($entrada2) || !empty($salida2)){
                            $asistenciasValidas[]= [
                                'id_personal'=> $id2,
                                'dia'=> $diaMes,
                                'entrada'=> $entrada2,
                                'salida'=> $salida2
                            ];
                        }
                    }
                    if(!empty($id3)){
                        $entrada3= $worksheet->getCell('AO'. $file)->getValue();
                        $salida3= $worksheet->getCell('AQ'. $file)->getValue();
                        
                        if(!empty($entrada3) || !empty($salida3)){
                            $asistenciasValidas[]= [
                                'id_personal'=> $id3,
                                'dia'=> $diaMes,
                                'entrada'=> $entrada3,
                                'salida'=> $salida3
                            ];
                        }
                    }
                }
            }
            if(!empty($asistenciasValidas)){
                $this->asistenciaModel->registrarAsistenciaMasiva($asistenciasValidas);

                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'mensaje' => 'Informe de asistencia procesado e insertado con éxito.'
                ];
            } else {
                $_SESSION['alerta'] = [
                    'tipo' => 'warning',
                    'mensaje' => 'No se encontraron datos de asistencia para procesar.'
                ];
            }

            header('Location: index.php?controllers=asistencia&accion=index');
            exit;   
            }catch (\Exception $e) {
                $_SESSION['alerta'] = [
                'tipo' => 'danger',
                'mensaje' => 'Error al leer el archivo excel: ' . $e->getMessage()
            ];
            header('Location: index.php?controllers=asistencia&accion=index');
            exit;
            }
    }                    
}