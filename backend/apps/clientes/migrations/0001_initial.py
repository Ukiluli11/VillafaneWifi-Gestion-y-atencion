"""Crea las tablas iniciales de Cliente y TelefonoCliente."""

# Generada por Django 5.2.17 el 2026-09-01.

import django.db.models.deletion
from django.db import migrations, models


class Migration(migrations.Migration):
    """Define la estructura inicial del módulo de clientes."""

    initial = True

    dependencies = [
    ]

    operations = [
        migrations.CreateModel(
            name='Cliente',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('tipo_documento', models.CharField(choices=[('DNI', 'DNI'), ('CUIT', 'CUIT'), ('PASAPORTE', 'Pasaporte'), ('OTRO', 'Otro')], default='DNI', max_length=20, verbose_name='tipo de documento')),
                ('numero_documento', models.CharField(max_length=30, verbose_name='número de documento')),
                ('nombre_razon_social', models.CharField(max_length=160, verbose_name='nombre o razón social')),
                ('tipo_cliente', models.CharField(choices=[('PERSONA', 'Persona'), ('EMPRESA', 'Empresa')], default='PERSONA', max_length=30, verbose_name='tipo de cliente')),
                ('contacto_calle', models.CharField(blank=True, max_length=120, verbose_name='calle de contacto')),
                ('contacto_numero', models.CharField(blank=True, max_length=20, verbose_name='número de contacto')),
                ('contacto_localidad', models.CharField(blank=True, max_length=100, verbose_name='localidad de contacto')),
                ('estado', models.CharField(choices=[('ACTIVO', 'Activo'), ('INACTIVO', 'Inactivo')], default='ACTIVO', max_length=20, verbose_name='estado')),
            ],
            options={
                'verbose_name': 'cliente',
                'verbose_name_plural': 'clientes',
                'db_table': 'cliente',
                'ordering': ('nombre_razon_social', 'id'),
                'constraints': [models.UniqueConstraint(fields=('tipo_documento', 'numero_documento'), name='uq_cliente_documento')],
            },
        ),
        migrations.CreateModel(
            name='TelefonoCliente',
            fields=[
                ('numero', models.CharField(max_length=30, primary_key=True, serialize=False, verbose_name='teléfono')),
                ('cliente', models.ForeignKey(db_column='id_cliente', on_delete=django.db.models.deletion.PROTECT, related_name='telefonos', to='clientes.cliente')),
            ],
            options={
                'verbose_name': 'teléfono del cliente',
                'verbose_name_plural': 'teléfonos de clientes',
                'db_table': 'cliente_telefono',
                'ordering': ('numero',),
            },
        ),
    ]
