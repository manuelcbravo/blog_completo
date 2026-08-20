---
titulo: 'Datos sensibles: no basta con saber quién entró, hay que saber para qué'
slug: expediente-clinico-registro-de-acceso
tipo: post
estado: borrador
categoria: Seguridad
etiquetas: [laravel, datos-personales, auditoria, multi-tenant, salud]
resumen: Un expediente clínico se consulta legítimamente cien veces al día. La bitácora que sólo guarda quién y cuándo no distingue una consulta legítima de una curiosidad. La columna que lo cambia todo es «motivo».
meta_descripcion: Cómo proteger expedientes clínicos en una aplicación Laravel multi-inquilino con bitácora de acceso, motivo obligatorio y consentimientos registrados.
hace_dias: 71
---

En una agenda clínica multi-consultorio, el expediente es el dato más delicado
que existe: diagnósticos, notas de evolución, estudios, antecedentes.

Bajo la ley mexicana es **dato personal sensible**, con un régimen más estricto
que un nombre o un teléfono. Y a diferencia de un número de tarjeta, no se puede
cifrar y olvidar: el personal lo necesita abierto, todos los días.

Lo que sigue es cómo quedó modelado, y la columna que resultó ser la más
importante de todo el módulo.

## El expediente no es una tabla

Es siete:

| Modelo | Qué guarda |
| --- | --- |
| `ClinicalRecord` | El expediente en sí |
| `ClinicalRecordHistory` | Antecedentes |
| `ClinicalRecordEvolutionNote` | Notas de evolución por consulta |
| `ClinicalRecordStudy` | Estudios y resultados |
| `ClinicalRecordAttachment` | Archivos adjuntos |
| `ClinicalRecordConsent` | **Consentimientos firmados** |
| `ClinicalRecordAccessLog` | **Quién lo abrió y para qué** |

Las cinco primeras son el modelado natural del dominio. Las dos últimas son las
que convierten un CRUD en algo que puedes defender ante una auditoría.

## La bitácora de acceso, y su columna decisiva

```php
class ClinicalRecordAccessLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'clinical_record_id',
        'user_id',
        'reason',
        'ip_address',
    ];
}
```

Cuatro campos. El que importa es **`reason`**.

Una bitácora que guarda quién y cuándo es lo que hace cualquier paquete de
auditoría, y no sirve para lo que hace falta aquí. Un expediente se abre
legítimamente decenas de veces: la doctora antes de la consulta, la recepcionista
para confirmar una cita, la administradora para facturar.

Con quién y cuándo, todas esas líneas se ven iguales. Y la línea de alguien que
abrió el expediente de una persona conocida por curiosidad también se ve igual.

**El motivo es lo que separa el acceso legítimo del que no lo es**, y tiene un
efecto que no esperaba: obligar a declararlo cambia el comportamiento antes de
que haya que auditar nada. Una pantalla que pregunta «¿para qué vas a abrir este
expediente?» hace que la gente se lo piense. La bitácora silenciosa no.

Es la diferencia entre una cámara de seguridad y una cámara de seguridad con un
letrero.

## Por qué no `owen-it/laravel-auditing` para esto

Uso ese paquete y lo recomiendo, pero registra **cambios**: crear, actualizar,
borrar.

El problema del expediente clínico es el contrario. La fuga no ocurre al
modificar, ocurre al **leer**. Alguien que abre un expediente y lo lee no genera
un solo renglón de auditoría de cambios, y sin embargo acaba de hacer exactamente
lo que la ley busca impedir.

Por eso la bitácora de acceso es una tabla propia, escrita explícitamente al
abrir, y no un efecto secundario de un observer de Eloquent.

```php
public function show(ClinicalRecord $expediente, MotivoAccesoRequest $request): Response
{
    ClinicalRecordAccessLog::create([
        'clinical_record_id' => $expediente->id,
        'user_id' => $request->user()->id,
        'reason' => $request->validated('reason'),
        'ip_address' => $request->ip(),
    ]);

    // ...
}
```

El motivo es un campo validado, no opcional. Si no viene, no se abre.

## Los consentimientos como tabla, no como casilla

`ClinicalRecordConsent` es una tabla y no un `boolean` en el expediente, por la
misma razón que ya escribí sobre bots: **el consentimiento es a un texto
concreto, en una fecha concreta**.

Una casilla `acepto_tratamiento = true` no responde a qué aceptó el paciente ni
cuándo. Una tabla con el documento, la versión, la fecha y la firma, sí. Y
permite lo que la práctica clínica de verdad necesita: consentimientos distintos
para procedimientos distintos, cada uno con su vigencia.

## Multi-inquilino: el aislamiento no puede ser opcional

Todos los modelos llevan `BelongsToTenant`, un trait que aplica un *global scope*
de Eloquent para filtrar por consultorio.

La virtud del global scope es que **no hay que acordarse**. Un `where` que se
escribe en cada consulta es un `where` que algún día alguien va a olvidar, y en
una aplicación multi-inquilino olvidarlo significa mostrarle a un consultorio los
expedientes de otro.

Con el scope, olvidarlo es imposible: `ClinicalRecord::all()` ya viene filtrado.

Dos advertencias que aprendí a poner:

**`withoutGlobalScopes()` debe ser rarísimo.** Cuando aparece en un controlador,
alguien tiene que justificarlo en la revisión de código.

**Las consultas crudas se saltan el scope.** Un `DB::table('clinical_records')`
no pasa por Eloquent y por lo tanto no filtra nada. En un módulo con datos
sensibles, la regla es que no hay consultas crudas.

## Lo que dejé fuera y por qué

**No cifré los campos en la base.** Suena a que debería, y en la práctica el
cifrado a nivel de columna rompe las búsquedas, los ordenamientos y los reportes,
a cambio de proteger contra un escenario —alguien con acceso al disco de la base
y sin acceso a la aplicación— que es mucho menos probable que el que sí ocurre: un
usuario legítimo mirando lo que no le toca.

Contra ese, lo que sirve es el control de acceso y la bitácora con motivo. El
cifrado en reposo lo resuelve el disco del servidor, no la aplicación.

**No puse borrado de la bitácora.** Ni siquiera para administradores. Una bitácora
que el administrador puede editar no es una bitácora.

## Lo que me llevo

Si tuviera que quedarme con una idea de este módulo: **para datos sensibles,
auditar los cambios no alcanza, hay que auditar las lecturas — y con motivo.**

Es una tabla de cuatro columnas, una pantalla más antes de abrir el expediente, y
convierte «confiamos en el equipo» en algo que se puede demostrar. Que es
exactamente lo que te van a pedir el día que alguien pregunte quién vio qué.
