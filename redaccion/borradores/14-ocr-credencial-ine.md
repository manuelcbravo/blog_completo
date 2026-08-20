---
titulo: 'Leer la credencial del INE: el código de barras del reverso ya no sirve'
slug: leer-la-credencial-del-ine-con-ocr
tipo: post
estado: borrador
categoria: Móvil
etiquetas: [react-native, ocr, ine, ml-kit, datos-personales]
resumen: 'El plan era leer el PDF417 del reverso: dato estructurado, determinista, sin ambigüedad. Los modelos nuevos lo sustituyeron por un QR cifrado. Todo tiene que salir de OCR, y eso cambia el diseño entero.'
meta_descripcion: Por qué el PDF417 de la credencial del INE ya no se puede leer, cómo extraer los datos con OCR desde React Native, y la regla de precedencia entre CURP, clave de elector y MRZ.
hace_dias: 1
importante: true
---

Escribí el plan de la app de campo con una idea que me parecía obvia: leer el
**PDF417** del reverso de la credencial. Es un código de barras bidimensional,
trae los datos estructurados, y un lector devuelve texto determinista. El OCR
quedaría como complemento para lo que faltara.

Es falso, y quiero dejarlo escrito para que nadie más pierda el tiempo.

## Qué pasó con el código del reverso

Los modelos nuevos de la credencial **sustituyeron el PDF417 por un QR de alta
densidad, comprimido y encriptado**.

No es un descuido ni una versión distinta del mismo formato. Ese QR está pensado
para que la aplicación *Valida INE-QR* verifique la autenticidad del plástico
contra los servidores del INE. No está pensado para que nadie extraiga los datos
de quien trae la credencial en la mano — que es, visto así, exactamente lo
correcto.

Un lector de códigos apuntado ahí devuelve bytes cifrados. No hay biblioteca, no
hay truco, no hay parámetro.

## De dónde sí salen los datos

Sólo dos zonas de la credencial son texto plano:

| Zona | Qué aporta |
| --- | --- |
| **MRZ del reverso** | `IDMEX`, el CIC, el OCR de 13 dígitos y, en formato fijo, fecha de nacimiento, sexo y nombre completo |
| **El frente** | Clave de elector, CURP, sección y domicilio, cada uno junto a su etiqueta impresa |

Las dos se leen con OCR. No hay atajo.

Eso cambia el problema por completo. Con un código de barras tienes un dato o no
lo tienes. Con OCR tienes **cinco lecturas parciales, algunas contradictorias, y
hay que decidir cuál gana**.

## La regla: lo que se valida solo gana sobre lo que se reconoció

Esta es la parte que de verdad importa, y no es de OCR sino de diseño de datos.

La CURP y la clave de elector **llevan la fecha de nacimiento y el sexo dentro de
su propia estructura**. Una CURP son 18 caracteres con posiciones fijas: las
letras del nombre, seis dígitos de fecha, el sexo, el estado. Si los 18 cuadran
con su formato, la fecha que sale de ahí es un hecho verificable, no una lectura.

El nombre reconocido sobre un plástico rayado, a contraluz, en la puerta de una
casa, es una interpretación.

De ahí sale el orden de precedencia:

```
CURP  →  clave de elector  →  MRZ del reverso  →  frente
```

Y de ahí sale la regla que más veces me ha salvado: **cuando dos fuentes que se
validan solas discrepan, no se resuelve automáticamente**. Se avisa y decide la
persona.

Un *merge* automático sobre datos de padrón es exactamente el tipo de error que
nadie detecta hasta que ya se propagó a cuarenta mil registros.

## El problema del siglo merece párrafo aparte

La clave de elector guarda el año de nacimiento **con dos dígitos** y no trae
nada que desempate el siglo. `85` puede ser 1985 o 2085 — y como 2085 no existe,
el problema real es al revés: `07` puede ser 1907 o 2007.

La CURP sí lo resuelve: su carácter 17 es un **dígito antes del año 2000 y una
letra a partir del 2000**. Fue precisamente el arreglo del efecto 2000 en el
registro mexicano.

Cuando sólo hay clave de elector, se elige el siglo que produce una edad con la
que existe una credencial de elector. Y si los dos siglos la producen, se avisa
en lugar de adivinar.

Es exactamente el error que genera gente de 1907 en un padrón. Cualquiera que
haya limpiado una base de datos electoral ha visto esos registros.

## Cómo se captura, y por qué no hay botón de obturador

La pantalla dispara sola cada 1.1 segundos y **acumula** lo que va reconociendo.
Va diciendo qué le falta —«ahora voltéala: el nombre está en el reverso»— y
cuando lo tiene todo, pasa sola a la pantalla de revisión.

La razón no es técnica. Quien captura tiene **la credencial en una mano y el
teléfono en la otra**, muchas veces de pie en la puerta de una casa, con el sol
de frente. Pedirle además que encuadre y apriete un botón es pedirle una foto
movida.

Después vienen dos pasos que no se saltan:

**Revisión.** Cada campo muestra de dónde salió —CURP, clave, reverso,
credencial— y se marcan los de poca confianza. **La persona confirma antes de
guardar. Nunca se guarda a ciegas.**

**Duplicado.** Se cruza contra los registros locales por clave de elector y se
avisa. Se avisa, no se bloquea: en campo hay credenciales mal leídas y gente que
legítimamente hay que recapturar.

## El costo: hay que salir de Expo Go

El OCR corre en el dispositivo con **ML Kit**
(`@react-native-ml-kit/text-recognition`): gratis, rápido y sin conexión.

Y es un módulo nativo, así que no corre en Expo Go. Exige un *development build*
con `expo-dev-client` y EAS Build — o sea instalar un `.apk` en cada teléfono de
campo en lugar de abrir una app de la tienda.

Está asumido, porque el requisito es capturar **sin señal**, y un OCR que sube la
foto a un servidor no sirve justo donde más hace falta.

Lo que sí hice fue degradar con elegancia: en un binario sin el módulo, la
pantalla lo dice y ofrece capturar a mano, en vez de quedarse en una cámara que
no reconoce nada y no explica por qué.

## Cuánto acierta, medido

Esto no lo estimé, lo medí. Hay una prueba que ejercita todo el desdoblado
—CURP, clave, MRZ, precedencia, acumulación al voltear— contra **3,000 claves de
elector reales** de la plataforma:

| Qué | Acierto |
| --- | --- |
| El formato se reconoce | 88.7% |
| El sexo coincide con lo guardado | 97.8% |
| El día y el mes coinciden | 93.1% |

Y lo interesante es el 11% que no reconoce, porque **no es un formato distinto**:
son claves vacías, truncadas a 12 o 17 caracteres, con una letra donde va un
dígito, y 29 CURP capturadas en el campo equivocado. O sea, suciedad de captura
previa.

Más revelador todavía: revisando uno por uno los casos donde el día o el mes
discrepan, **el error está en la fecha guardada** —14 contra 15, 10 contra 11—,
no en la clave. Para esas filas la clave de elector es la fuente más confiable de
las dos.

Ese hallazgo no venía en el plan. Salió de correr la prueba contra datos reales
en vez de contra un archivo de ejemplo.

## Lo que no se guarda

La imagen de una credencial es dato personal, y en el reverso hay huella y firma.

El diseño por omisión: **se extraen los campos y la imagen no se conserva**. Si
el flujo llegara a exigir guardarla, que sea con consentimiento registrado,
subida al servidor y borrado local en cuanto confirme.

Es mucho más barato decidir eso ahora que después de tener cuarenta mil
credenciales repartidas en los teléfonos de campo.

## Si vas a hacer algo parecido

Tres cosas, en orden:

**No planees sobre el PDF417.** Ya no está. Toda arquitectura que dependa de
código de barras estructurado en la credencial mexicana nace muerta.

**Ordena tus fuentes por cuánto se validan solas**, no por cuánto texto aportan.
Y no fusiones automáticamente lo que discrepa.

**Mide contra datos reales antes de confiar.** Correr el desdoblado contra tres
mil claves de producción me dijo dos cosas que no habría sabido de otro modo: que
el reconocimiento sirve, y que parte de lo que tengo guardado está peor que lo
que reconozco.
