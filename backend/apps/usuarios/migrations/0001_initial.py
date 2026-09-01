"""Crea las tablas iniciales de Usuario, Empleado y Administrador."""

# Generada por Django 5.2.17 el 2026-09-01.

import apps.usuarios.managers
import django.db.models.deletion
from django.conf import settings
from django.db import migrations, models


class Migration(migrations.Migration):
    """Define la estructura inicial del módulo de usuarios."""

    initial = True

    dependencies = [
    ]

    operations = [
        migrations.CreateModel(
            name='Usuario',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('password', models.CharField(max_length=128, verbose_name='password')),
                ('last_login', models.DateTimeField(blank=True, null=True, verbose_name='last login')),
                ('nombre_usuario', models.CharField(max_length=150, unique=True, verbose_name='nombre de usuario')),
                ('is_active', models.BooleanField(default=True, verbose_name='activo')),
                ('is_staff', models.BooleanField(default=False, verbose_name='acceso al panel')),
                ('is_superuser', models.BooleanField(default=False, verbose_name='administrador general')),
                ('fecha_alta', models.DateTimeField(auto_now_add=True, verbose_name='fecha de alta')),
            ],
            options={
                'verbose_name': 'usuario',
                'verbose_name_plural': 'usuarios',
                'db_table': 'usuario',
            },
            managers=[
                ('objects', apps.usuarios.managers.GestorUsuario()),
            ],
        ),
        migrations.CreateModel(
            name='Administrador',
            fields=[
                ('usuario', models.OneToOneField(db_column='id_usuario', on_delete=django.db.models.deletion.CASCADE, primary_key=True, related_name='perfil_administrador', serialize=False, to=settings.AUTH_USER_MODEL)),
            ],
            options={
                'verbose_name': 'administrador',
                'verbose_name_plural': 'administradores',
                'db_table': 'administrador',
            },
        ),
        migrations.CreateModel(
            name='Empleado',
            fields=[
                ('usuario', models.OneToOneField(db_column='id_usuario', on_delete=django.db.models.deletion.CASCADE, primary_key=True, related_name='perfil_empleado', serialize=False, to=settings.AUTH_USER_MODEL)),
                ('area', models.CharField(choices=[('administracion', 'Administración'), ('soporte', 'Soporte técnico'), ('atencion_cliente', 'Atención al cliente')], max_length=30, verbose_name='área')),
            ],
            options={
                'verbose_name': 'empleado',
                'verbose_name_plural': 'empleados',
                'db_table': 'empleado',
            },
        ),
    ]
