---
titulo: Salir de Expo Go tiene un precio, y a veces hay que pagarlo
slug: salir-de-expo-go-development-build
tipo: post
estado: borrador
categoria: Móvil
etiquetas: [react-native, expo, eas, android, ml-kit]
resumen: Expo Go es lo que hace que React Native se sienta ligero. El día que necesitas un módulo nativo se acaba, y hay que decidir entre cambiar el requisito o cambiar la forma de distribuir la app.
meta_descripcion: Cuándo un proyecto de Expo necesita un development build, qué se pierde al salir de Expo Go y cómo degradar la aplicación cuando el módulo nativo no está.
hace_dias: 64
---

Expo Go es la razón por la que React Native con Expo se siente ligero. Instalas
una app de la tienda, escaneas un QR y tu proyecto corre en el teléfono. Sin
Android Studio, sin Xcode, sin compilar nada.

Funciona hasta que necesitas un módulo nativo que Expo Go no trae dentro.

## Cómo me pasó

La app de campo tenía que leer credenciales del INE con OCR, sin conexión. La
opción evidente es **ML Kit** de Google, vía
`@react-native-ml-kit/text-recognition`: gratis, rápido, corre en el dispositivo.

Y es código nativo. Expo Go es un binario ya compilado con un conjunto fijo de
módulos; el que tú instalas por npm no aparece por arte de magia dentro de una
app que ya está en la tienda.

Ahí tienes tres caminos, y conviene verlos los tres antes de elegir.

## Camino 1: cambiar el requisito

Hacer el OCR en el servidor: la app toma la foto, la sube, un modelo de visión
extrae los datos.

Para muchos casos es la respuesta correcta y sale más barata. Aquí no servía: el
requisito era capturar **sin señal**, en colonias donde no hay datos móviles. Un
OCR que necesita subir una foto no funciona justo donde más falta hace.

Vale la pena hacerse la pregunta antes de descartarla. La mitad de las veces el
requisito de «offline» es un deseo, no una restricción, y en ese caso cambiarlo
te ahorra todo lo que sigue.

## Camino 2: buscar si Expo ya lo trae

Antes de asumir que hace falta salir, revisar el SDK. Expo incorpora cada versión
más módulos, y varias cosas que hace años exigían un binario propio ya vienen
dentro: cámara, ubicación, almacenamiento seguro, SQLite, notificaciones,
biométricos.

En mi app, cinco de las seis capacidades nativas que necesitaba estaban en el
SDK 54. La sexta era el OCR.

## Camino 3: development build

Un *development build* es tu propia versión de Expo Go: la misma experiencia de
recarga en caliente y QR, pero compilada con **tus** módulos nativos dentro.

```bash
npx expo install expo-dev-client
npx expo run:android
```

O con EAS, sin necesidad de tener el entorno nativo instalado:

```bash
eas build --profile development --platform android
```

Y a partir de ahí el desarrollo se siente igual que antes: `npm start`, el QR, la
recarga al guardar. Lo que cambia es que ese binario hay que instalarlo una vez
en cada teléfono.

## Lo que de verdad cuesta

No es el comando. Es lo que viene después.

**La distribución deja de ser gratis.** Antes, un nuevo integrante del equipo
instalaba Expo Go de la tienda y en un minuto estaba viendo la app. Ahora hay que
mandarle un `.apk` y explicarle cómo instalar una app fuera de la tienda, con
Android preguntándole si de verdad confía en el origen.

**Los builds tardan.** Un build de EAS son entre cinco y quince minutos. Si
cambias una dependencia nativa a media tarde, ese es el precio de probarlo.

**Cada actualización de dependencias nativas exige rebuild.** El JavaScript sigue
recargándose al instante; el resto no. Y la parte molesta es que no siempre es
evidente cuál de las dos cambió.

**iOS se complica más.** En Android repartes el `.apk` y ya. En iOS hay que
pasar por TestFlight o por un perfil de aprovisionamiento, con la cuenta de
desarrollador de por medio.

## La decisión

En mi caso estaba clara: el requisito de campo era capturar sin señal, y el
único OCR que cumple eso es on-device. La app se distribuye por `.apk` a los
teléfonos del equipo, que además no son del público general sino de personal de
campo identificado.

Cuando la app va a la tienda para usuarios finales, el `.apk` deja de ser un
problema porque de todos modos ibas a publicar un binario propio. **El costo real
de salir de Expo Go es sobre todo durante el desarrollo, no en producción.**

## Degradar bien

Lo que sí hice, y recomiendo a cualquiera que dependa de un módulo nativo:

**En un binario donde el módulo no está, la pantalla lo dice y ofrece capturar a
mano.**

Suena obvio y casi nadie lo hace. Lo normal es que la pantalla de la cámara se
abra, no reconozca nada, y el usuario se quede diez minutos moviendo el teléfono
convencido de que lo está haciendo mal.

```tsx
const hayOcr = TextRecognition !== undefined;

if (!hayOcr) {
    return (
        <AvisoCaptura
            mensaje="Esta versión no incluye el lector de credenciales. Captura los datos a mano."
            onCapturarAMano={irAFormulario}
        />
    );
}
```

Sirve para tres situaciones reales: alguien abrió el proyecto en Expo Go por
costumbre, quedó un binario viejo en un teléfono, o estás probando en un
simulador sin el módulo. En las tres, el mensaje ahorra una llamada de soporte.

## Lo que se puede probar sin teléfono

La otra mitigación, y la que más rendimiento me dio: **saqué toda la lógica que
no toca la cámara a funciones puras, con pruebas que corren en Node.**

El desdoblado de una CURP, el de una clave de elector, el análisis de la MRZ, las
reglas de precedencia, la acumulación de lecturas al voltear la credencial: nada
de eso necesita un teléfono. Es texto entrando y un objeto saliendo.

```bash
npm run prueba:ine
```

El módulo nativo sólo aporta la cadena de texto reconocida. Todo lo demás
—que es donde están los errores interesantes— se prueba en la máquina, en
segundos, sin compilar, sin QR y sin cable.

Si vas a depender de un módulo nativo, esa frontera es lo primero que hay que
dibujar. Convierte «tengo que salir de Expo Go» en una molestia de distribución
en vez de en un problema que te frena el desarrollo entero.
