# Tienda Gym — Sitio Web Corporativo

Sitio de e-commerce de la empresa Tienda Gym (venta de equipos y accesorios de gimnasio),
diseñado, desarrollado y mantenido enteramente por mí con WordPress, Divi y WooCommerce.

**Sitio en vivo:** [tiendagym.com](https://tiendagym.com/)

> **Nota:** este no es un repositorio de código tradicional. El sitio corre sobre WordPress
> (Divi + WooCommerce), no sobre un framework versionado en Git. Por eso este repo documenta
> el trabajo real hecho sobre el sitio — con capturas del resultado y, en `codigo/`, el HTML,
> CSS, PHP y JavaScript reales que escribí para las dos funcionalidades más importantes que
> construí a medida (sanitizados de datos financieros/de acceso, nada más).

![Home](docs/screenshots/home.png)

## Qué es

Tienda web completa para la venta de equipos de gimnasio (línea profesional, línea hogar,
cardio, peso y accesorios), con catálogo, carrito, checkout y — la parte más interesante —
un sistema propio de cotizaciones que reemplazó un proceso manual que le costaba tiempo al
equipo de ventas.

## El problema que resolví: cotizaciones que consumían tiempo del equipo de ventas

Los clientes de Tienda Gym suelen pedir varios productos combinados y cotizaciones
personalizadas (equipar un gimnasio completo, por ejemplo), no compras simples de un solo
producto. Antes, cada una de esas cotizaciones la armaba manualmente un vendedor: buscar
precios, calcular subtotales, armar el documento. Con volumen, eso empezó a quitarle mucho
tiempo al equipo comercial.

**Lo que construí:** un cotizador de autoservicio integrado en el propio sitio, para que sea
el cliente quien arma su cotización, y el vendedor solo la reciba lista para confirmar.

- **Dos formas de agregar productos al cotizador:**
  - Un **widget flotante de arrastrar y soltar** que aparece en todas las páginas del catálogo:
    el cliente arrastra la tarjeta de cualquier producto hacia el panel y este se guarda para
    la cotización (usando `localStorage`, sin necesidad de cuenta ni login).
  - Un **buscador en vivo** dentro de la página `/cotizador`, que consulta la Store API de
    WooCommerce en tiempo real para agregar productos sin salir de esa página.
- **Reto técnico:** varios productos son variables (cambian de precio según talla, color,
  capacidad, etc.), y la Store API de WooCommerce no expone el precio por variación en el
  listado. Lo resolví haciendo *scraping* del formulario de variaciones que WooCommerce ya
  inyecta en el HTML de la página de cada producto (`data-product_variations`), y mostrando
  esas opciones tanto en el widget flotante como en el buscador antes de agregar el producto.
- **Validación de datos del cliente** en tiempo real: nombre solo con letras, documento y
  celular solo con números (celular limitado a 10 dígitos), correo restringido a dominios
  comunes, dirección obligatoria — con mensajes de error específicos por campo y un mensaje
  general si falta algo (ej. no hay productos agregados).
- **Tres formas de cerrar la cotización:**
  - Descargar un **PDF** con el membrete y los colores de la empresa (generado en el navegador
    con jsPDF, incluyendo el logo cargado dinámicamente).
  - Descargar un **Excel** con el mismo formato, con celdas combinadas, colores corporativos y
    el logo insertado (generado con ExcelJS).
  - **Enviar por WhatsApp** directo al número de ventas, con el mensaje ya armado (cliente,
    productos, cantidades y total).
- **Registro automático:** cada cotización generada se envía también, en segundo plano, a un
  Google Apps Script que la guarda en una hoja de Google Sheets — así el equipo de ventas
  tiene un historial de todas las cotizaciones sin necesidad de una base de datos propia.
- Cada cotización recibe un número consecutivo único (`TG-AÑO-XXXXXX`), guardado en
  `sessionStorage` para no duplicar el registro si el cliente descarga el PDF y el Excel de la
  misma cotización.

Todo esto lo implementé sin salir de WordPress: el HTML/CSS/JS de la página del cotizador vive
en un módulo de código de Divi, y el widget flotante (que debe aparecer en *todas* las páginas)
lo inyecté como snippet PHP con el plugin **Code Snippets**, enganchado al hook `wp_footer`.
El código real de ambas piezas está en [`codigo/`](codigo/).

## Checkout con múltiples medios de pago

Además del cotizador, configuré el checkout de WooCommerce con varios métodos de pago:
Mercado Pago, tarjeta de crédito/débito, PSE y **Addi** (pago a cuotas). Al integrar Addi
apareció un bug de contraste en el checkout clásico de WooCommerce — los mensajes de error se
mostraban en texto azul sobre fondo azul, ilegibles — que resolví con CSS personalizado desde
Divi (Opciones del Tema → General → CSS Personalizado), sin tocar archivos del tema ni del
plugin.

![Medios de pago](docs/screenshots/medios-de-pago.png)

## Capturas

| | |
|---|---|
| ![Catálogo](docs/screenshots/catalogo-productos.png) Catálogo de productos | ![Widget flotante](docs/screenshots/widget-cotizador-flotante.png) Widget flotante de arrastrar y soltar |
| ![Página del cotizador](docs/screenshots/pagina-cotizador.png) Página del cotizador con datos del cliente | ![Checkout con Addi](docs/screenshots/checkout-addi.png) Checkout con Addi como método de pago |

![Snippet de código](docs/screenshots/editor-snippet-codigo.png)
*El widget flotante inyectado como snippet PHP/JS/CSS con el plugin Code Snippets de WordPress.*

## Stack técnico

WordPress · Divi Builder · WooCommerce (Store API) · Code Snippets (PHP/JS/CSS a medida) ·
JavaScript vanilla · jsPDF · ExcelJS · Google Apps Script (registro en Google Sheets) · Addi
(pasarela de pago a cuotas)

## Otros proyectos

- [TiendaGymBI](https://github.com/Molaneitor/TiendaGymBI) — pipeline de datos de ventas,
  costos e inventario de la misma empresa, con Python, SQL Server y Power BI.
