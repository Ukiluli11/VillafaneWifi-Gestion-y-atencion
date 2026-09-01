"""Crea las tablas iniciales de Plan y Servicio."""

# Generada por Django 5.2.17 el 2026-09-01.

import django.core.validators
import django.db.models.deletion
import django.db.models.functions.text
import django.utils.timezone
from django.db import migrations, models


class Migration(migrations.Migration):
    """Define la estructura inicial del módulo de servicios."""

    initial = True

    dependencies = [
        ('clientes', '0001_initial'),
    ]

    operations = [
        migrations.CreateModel(
            name='Plan',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('nombre', models.CharField(max_length=100, verbose_name='nombre')),
                ('velocidad_mbps', models.PositiveIntegerField(validators=[django.core.validators.MinValueValidator(1)], verbose_name='velocidad en Mbps')),
                ('precio_vigente', models.DecimalField(decimal_places=2, max_digits=12, validators=[django.core.validators.MinValueValidator(0)], verbose_name='precio vigente')),
                ('estado', models.CharField(choices=[('ACTIVO', 'Activo'), ('INACTIVO', 'Inactivo')], default='ACTIVO', max_length=20, verbose_name='estado')),
            ],
            options={
                'verbose_name': 'plan',
                'verbose_name_plural': 'planes',
                'db_table': 'plan',
                'ordering': ('velocidad_mbps', 'nombre'),
                'constraints': [models.UniqueConstraint(django.db.models.functions.text.Lower('nombre'), name='uq_plan_nombre_minusculas'), models.CheckConstraint(condition=models.Q(('velocidad_mbps__gt', 0)), name='ck_plan_velocidad_positiva'), models.CheckConstraint(condition=models.Q(('precio_vigente__gte', 0)), name='ck_plan_precio_no_negativo')],
            },
        ),
        migrations.CreateModel(
            name='Servicio',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('instalacion_calle', models.CharField(max_length=120, verbose_name='calle de instalación')),
                ('instalacion_numero', models.CharField(blank=True, max_length=20, verbose_name='número de instalación')),
                ('instalacion_localidad', models.CharField(max_length=100, verbose_name='localidad de instalación')),
                ('dia_vencimiento', models.PositiveSmallIntegerField(validators=[django.core.validators.MinValueValidator(1), django.core.validators.MaxValueValidator(31)], verbose_name='día de vencimiento')),
                ('fecha_alta', models.DateField(default=django.utils.timezone.localdate, verbose_name='fecha de alta')),
                ('ip', models.GenericIPAddressField(blank=True, null=True, verbose_name='dirección IP')),
                ('mac', models.CharField(blank=True, max_length=17, null=True, validators=[django.core.validators.RegexValidator(message='La dirección MAC debe tener seis pares hexadecimales.', regex='^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$')], verbose_name='dirección MAC')),
                ('estado', models.CharField(choices=[('ACTIVO', 'Activo'), ('SUSPENDIDO', 'Suspendido'), ('INACTIVO', 'Inactivo')], default='ACTIVO', max_length=20, verbose_name='estado')),
                ('cliente', models.ForeignKey(db_column='id_cliente', on_delete=django.db.models.deletion.PROTECT, related_name='servicios', to='clientes.cliente')),
                ('plan', models.ForeignKey(db_column='id_plan', on_delete=django.db.models.deletion.PROTECT, related_name='servicios', to='servicios.plan')),
            ],
            options={
                'verbose_name': 'servicio',
                'verbose_name_plural': 'servicios',
                'db_table': 'servicio',
                'ordering': ('cliente_id', 'id'),
                'constraints': [models.UniqueConstraint(fields=('ip',), name='uq_servicio_ip'), models.UniqueConstraint(fields=('mac',), name='uq_servicio_mac'), models.CheckConstraint(condition=models.Q(('dia_vencimiento__gte', 1), ('dia_vencimiento__lte', 31)), name='ck_servicio_dia_vencimiento')],
            },
        ),
    ]
