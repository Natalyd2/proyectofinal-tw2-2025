<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\User;
use App\Models\Materia;
use App\Models\MateriasxUsuario;
use App\Models\Calificacion;

class MateriasxUsuarioController extends Controller
{
    function index($id){
        $usuario = User::with('tipo')->findOrFail($id);

        $materiasAsignadas = MateriasxUsuario::with(['materia', 'calificaciones'])
        ->where('users_id', $id)
        ->get();
        foreach($materiasAsignadas as $asignacion){
            $promedio = $asignacion->calificaciones->avg('calificaciones');
            $asignacion->promedio = $promedio ? round( $promedio, 2 ) : 0;
        }
        $materiasAsignadasIds = $materiasAsignadas->pluck('materias_id')->toArray();
        $materiasDisponibles = Materia::whereNotIn('id', $materiasAsignadasIds)->get();
        return view('materiasxusuario.index', compact('usuario', 'materiasAsignadas', 'materiasDisponibles'));
    }
    function asignar(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'materia_id' => 'required|exists:materias,id'
        ]);
        if($validator->fails() ){
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        try{
            $existe = MateriasxUsuario::where('materias_id', $request->materia_id)
            ->where('users_id', $id)
            ->exists();
            if( $existe ){
                return response()->json([
                    'success' => false,
                    'message' => 'Esta materia ya esta asignada a este usuario'
                ], 400);
            }
            MateriasxUsuario::create([
                'materias_id' => $request->materias_id,
                'users_id' => $id
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Materia asignada correctamente'
            ]);
        }
        catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar la materia'
            ], 500);
        }
    }
    function desasignar($asignacion_id){
        try{
            $asignacion = MateriasxUsuario::findOrFail($asignacion_id);
            $usuario_id = $asignacion->users_id;
            Calificacion::where('materias_x_usuarios_id', $asignacion_id)->delete();
            $asignacion->delete();
            return redirect()
            ->route('materiasxusuario.index', $usuario_id)
            ->with('success', 'Materia desasignada correctamente');
        }
        catch(\Exception $e){
         return redirect()
         ->back()
         ->with('error', 'Error al desasignar la materia');

        }
    }
}
