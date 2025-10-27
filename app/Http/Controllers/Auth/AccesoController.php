<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AccesoController extends Controller
{
    public function mostrarFormulario(){
        if( Auth::check() ){
            return $this->redirigirSegunTipo(Auth::user());
        }
        return view('auth.acceso');
    }
    public function iniciarSesion(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6'
        ],[
            'email.required' => 'El correo elcetronico es obligatorio',
            'email.email' => 'Debe ingresar un correo electronico valido',
            'email.max' => 'El correo electronico no puede tener mas de 255 caracteres',
            'password.required' => 'La contraseña es obligatoria',
            'password.string' => 'La contraseña debe ser una cadena de texto',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
        ]);

        if( $validator->fails() ){
            if( $request->ajax() ){
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            return redirect()
            ->back()
            ->withErrors( $validator )
            ->withImput( $request->only('email') );
        }

        $credenciales = $request->only('email', 'password');

        if( Auth::attempt( $credenciales) ){
            $request->session()->regenerate();
            $usuario = Auth::user();

            if( !$usuario->tipo ){
                Auth::logout();
                if ($request->ajax() ){
                    return response()->json([
                        'success' => false,
                        'message' => 'Usuario sin tipo asignado. Contacte al administrador.'
                    ], 401);
                }
                return redirect()
                ->back()
                ->withErrors(['email' => 'Usuario sin tipo asignado.'])
                ->withInput($request->only('email'));
            }

            if( $request->ajax() ){
                return response()->json([
                    'success' => true,
                    'message' => 'Inicio de sesion exitoso',
                    'redirect' => $this->obtenerUrlRedireccion($usuario)
                ]);
            }
            return $this->redirigirSegunTipo($usuario);
        }
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Las credenciales proporcionadas son incorrectas.'
            ], 401);
        }
        return redirect()
        ->back()
        ->withErrors(['email' => 'Las credenciales proporcionadas son incorrectas.'])
        ->withInput($request->only('email'));
    }
    public function cerrarSesion(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('acceso');
    }
    private function redirigirSegunTipo($usuario){
        $tipoUsuario = $usuario->tipo->tipo;
        switch ($tipoUsuario) {
            case 'admin':
            case 'profesor':
                // Admin y profesores van al dashboard de usuarios
                return redirect()->route('usuarios.index');
            case 'estudiante':
                // Estudiantes van a sus materias asignadas
                return redirect()->route('materiasxusuario.index', $usuario->id);
            default:
                Auth::logout();
                return redirect()->route('acceso')
                ->withErrors(['email' => 'Tipo de usuario no válido.']);
        }
    }
    private function obtenerUrlRedireccion($usuario){
        $tipoUsuario = $usuario->tipo->tipo;
        switch ($tipoUsuario) {
            case 'admin':
            case 'profesor':
                return route('usuarios.index');
            case 'estudiante':
                return route('materiasxusuario.index', $usuario->id);
            default:
                return route('acceso');
        }     
    }
}
