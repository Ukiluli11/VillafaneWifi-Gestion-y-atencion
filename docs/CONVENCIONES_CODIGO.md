# Convenciones de código

## Paradigma

El sistema se desarrollará principalmente mediante programación orientada a objetos.
Las entidades del dominio se representarán con clases y la lógica se organizará en
objetos con responsabilidades concretas. Se evitarán clases que concentren funciones
sin relación entre sí.

## Idioma

Los nombres propios del proyecto se escribirán en español:

- módulos y paquetes;
- clases del dominio;
- funciones y métodos propios;
- variables y constantes propias;
- campos de modelos y mensajes destinados al usuario.

Se conservarán en inglés los nombres impuestos por Python, Django o una API externa.
Algunos ejemplos son `manage.py`, `settings`, `is_staff`, `is_superuser`, `save`,
`clean`, `__str__` y los nombres exactos recibidos por webhooks externos.

## Documentación

- Cada archivo Python debe comenzar con un docstring que explique su responsabilidad.
- Cada clase y función propia debe incluir un docstring breve que indique qué hace.
- Los comentarios internos deben explicar decisiones o reglas, no repetir literalmente
  lo que ya expresa el código.
- Las reglas de negocio importantes deben acompañarse con pruebas automatizadas.

## Ejemplo

```python
class ServicioCliente:
    """Coordina las operaciones permitidas sobre los clientes."""

    def dar_de_alta(self, datos_cliente):
        """Registra un cliente nuevo luego de validar sus datos obligatorios."""
```

