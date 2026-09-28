import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../services/auth';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './register.html',
  styleUrl: './register.css',
})
export class Register {
  nombre = '';
  correo = '';
  password = '';
  passwordConfirmation = '';
  idioma = 'es';

  mensaje = '';
  error = '';

  constructor(private authService: AuthService) {}

  registrar(): void {
    this.mensaje = '';
    this.error = '';

    this.authService
      .register({
        nombre: this.nombre,
        correo: this.correo,
        password: this.password,
        password_confirmation: this.passwordConfirmation,
        idioma: this.idioma,
      })
      .subscribe({
        next: () => {
          this.mensaje = 'Usuario registrado correctamente.';
          this.error = '';

          this.nombre = '';
          this.correo = '';
          this.password = '';
          this.passwordConfirmation = '';
        },

        error: (error) => {
          this.error = error?.error?.error?.message ?? 'No se pudo registrar el usuario.';
        },
      });
  }
}
