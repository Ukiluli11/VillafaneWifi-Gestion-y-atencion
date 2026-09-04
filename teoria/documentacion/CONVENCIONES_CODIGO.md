# Convenciones de código

## Paradigma y responsabilidades

El sistema utiliza programación orientada a objetos. Los modelos representan
entidades y comportamiento propio; los servicios de `app/Dominio` coordinan
casos de uso; los controladores traducen solicitudes y respuestas sin contener
reglas de negocio; los Form Requests validan la entrada.

## Idioma

Los nombres propios del dominio se escriben mayormente en español: clases,
métodos, variables, tablas, campos, rutas y mensajes. Se conservan en inglés los
nombres impuestos por PHP, Laravel, Composer, npm o una API externa, como
`app`, `config`, `routes`, `resources`, `Request`, `Model` y `middleware`.

## Estilo y seguridad

- Seguir PSR-12 y aplicar Laravel Pint antes de cerrar un cambio.
- Usar Eloquent y relaciones declaradas; evitar SQL manual innecesario.
- Validar toda entrada con Form Requests y autorizar acciones en middleware o
  políticas del dominio.
- No guardar credenciales ni secretos en archivos versionados.
- Evitar borrados físicos cuando el modelo define estados de baja.
- Prevenir consultas N+1 mediante carga anticipada de relaciones.

## Documentación y pruebas

Los comentarios y PHPDoc explican contratos, tipos complejos o decisiones que
no resultan evidentes en el código. No deben repetir el nombre del método.
Cada regla de negocio importante debe estar acompañada por una prueba automatizada.

```php
final class ServicioClientes
{
    /** @param array<string, mixed> $datos */
    public function crear(array $datos): Cliente
    {
        return Cliente::create($datos);
    }
}
```
