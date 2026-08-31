<?php
/**
 * WordPress snippet (via the "Code Snippets" plugin) that injects the floating
 * drag-and-drop-to-quote widget on every page of the site except the /cotizador
 * page itself.
 *
 * Flow: every <li class="product"> in the WooCommerce catalog becomes
 * "draggable"; when a product is dropped onto the panel, if it's a product with
 * variations (size/color/capacity, etc.) the product page's variations form is
 * scraped (WooCommerce doesn't expose that through the Store API) to show the
 * options before saving it. The chosen product is saved to localStorage, which
 * the quoting page (see codigo/pagina-cotizador.html) reads to populate the
 * table automatically.
 *
 * Note: the visible UI text (labels, buttons) is left in Spanish on purpose —
 * it's the real copy that ships on the live, Spanish-language site.
 */

add_action('wp_footer', 'tg_cotizador_flotante');
function tg_cotizador_flotante() {
    if (is_page('cotizador')) {
        return;
    }
    ?>
    <div id="tg-drop-cotizador">
        <div class="tg-drop-label">
            Arrastra tu producto aquí
        </div>
        <div class="tg-drop-inner" id="tg-drop-area">
            <div class="tg-drop-default">
                <div class="tg-drop-icon">+</div>
                <div class="tg-drop-text">Arrastra un producto aquí</div>
            </div>
            <div class="tg-producto-preview" style="display:none;">
                <button type="button" id="tg-remove-producto">×</button>
                <img id="tg-preview-img" src="" alt="">
                <div id="tg-preview-nombre"></div>
				<div id="tg-preview-variaciones"></div>
                <button type="button" id="tg-agregar-cotizador">
                    Agregar al cotizador
                </button>
            </div>
        </div>
    </div>
    <style>
    #tg-drop-cotizador{
        position:fixed;
        right:0;
        top:100px;
        width:38px;
        height:380px;
        background:#ffe600;
        border-radius:20px 0 0 20px;
        z-index:99;
        box-shadow:0 10px 30px rgba(0,0,0,0.25);
        display:flex;
        justify-content:center;
        align-items:center;
        overflow:hidden;
        transition:all .25s ease;
    }
    .tg-drop-label{
        position:absolute;
        transform:rotate(-90deg);
        white-space:nowrap;
        font-size:15px;
        font-weight:700;
        color:#222;
        letter-spacing:1px;
    }
    #tg-drop-cotizador.tg-abierto{
        right:35px;
        width:230px;
        min-height:420px;
        height:auto;
        padding:25px;
        border-radius:40px;
        justify-content:flex-start;
        align-items:flex-start;
    }
    #tg-drop-cotizador.tg-abierto .tg-drop-label{
        display:none;
    }
    #tg-drop-cotizador .tg-drop-inner{
        display:none;
    }
    #tg-drop-cotizador.tg-abierto .tg-drop-inner{
        display:flex;
    }
    .tg-drop-inner{
        width:100%;
        min-height:190px;
        border:3px dashed #222;
        background:#d9d9d9;
        flex-direction:column;
        justify-content:center;
        align-items:center;
        text-align:center;
        padding:10px;
        box-sizing:border-box;
    }
    .tg-drop-icon{
        font-size:55px;
        line-height:1;
        margin-bottom:10px;
    }
    .tg-drop-text{
        font-size:15px;
        font-weight:600;
        color:#222;
    }
    .tg-producto-preview{
        position:relative;
        width:100%;
    }
    #tg-remove-producto{
        position:absolute;
        top:-8px;
        right:-8px;
        width:24px;
        height:24px;
        border:none;
        border-radius:50%;
        background:#000;
        color:#fff;
        font-size:18px;
        font-weight:800;
        line-height:22px;
        cursor:pointer;
        z-index:2;
    }
    .tg-producto-preview img{
        max-width:100%;
        max-height:85px;
        object-fit:contain;
        margin-bottom:8px;
    }
    #tg-preview-nombre{
        font-size:13px;
        font-weight:800;
        color:#111;
        line-height:1.2;
        text-transform:uppercase;
        margin-bottom:10px;
    }
    #tg-agregar-cotizador{
        width:100%;
        background:#000;
        color:#fff;
        border:none;
        padding:10px;
        font-size:12px;
        font-weight:800;
        cursor:pointer;
        text-transform:uppercase;
    }
    #tg-agregar-cotizador:hover{
        background:#222;
    }
    .tg-drop-hover{
        background:#f2f2f2;
        border-color:#000;
    }
    @media(max-width:980px){
        #tg-drop-cotizador{
            top:90px;
        }
        #tg-drop-cotizador.tg-abierto{
            width:180px;
            min-height:260px;
            right:25px;
        }
    }
    @media(max-width:768px){
        #tg-drop-cotizador{
            width:30px;
            height:250px;
            top:80px;
        }
        #tg-drop-cotizador.tg-abierto{
            width:150px;
            min-height:220px;
            right:25px;
        }
        .tg-drop-icon{
            font-size:40px;
        }
        .tg-drop-text{
            font-size:13px;
        }
        .tg-drop-label{
            font-size:12px;
        }
        #tg-preview-nombre{
            font-size:11px;
        }
        #tg-agregar-cotizador{
            font-size:10px;
            padding:8px;
        }
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const panel = document.getElementById('tg-drop-cotizador');
        const dropArea = document.getElementById('tg-drop-area');
        const defaultContent = document.querySelector('.tg-drop-default');
        const preview = document.querySelector('.tg-producto-preview');
        const previewImg = document.getElementById('tg-preview-img');
        const previewNombre = document.getElementById('tg-preview-nombre');
        const removeBtn = document.getElementById('tg-remove-producto');
        const agregarBtn = document.getElementById('tg-agregar-cotizador');
		const previewVariaciones = document.getElementById('tg-preview-variaciones');
        let productoActual = null;
        function limpiarPrecio(textoPrecio) {
            if (!textoPrecio) return 0;
            let precioLimpio = textoPrecio
                .replace(/\$/g, '')
                .replace(/\./g, '')
                .replace(/,/g, '')
                .replace(/[^\d]/g, '');
            return Number(precioLimpio) || 0;
        }

		function formatoCOP(valor) {
  			return new Intl.NumberFormat("es-CO", {
    		style: "currency",
    		currency: "COP",
    		maximumFractionDigits: 0
  			}).format(valor);
		}

		function limpiarHTML(texto) {
  			const div = document.createElement("div");
  			div.innerHTML = texto || "";
  			return div.textContent || div.innerText || "";
		}

		function obtenerIdProductoDesdeClase(producto) {
  			const clase = Array.from(producto.classList).find(c => c.startsWith("post-"));
  			return clase ? clase.replace("post-", "") : "";
		}

		async function obtenerVariacionesDesdePagina(linkProducto) {
  			const respuesta = await fetch(linkProducto);
  			const html = await respuesta.text();
  			const doc = new DOMParser().parseFromString(html, "text/html");
  			const formVariaciones = doc.querySelector(".variations_form");
  			if (!formVariaciones) return [];
  			const dataVariaciones = formVariaciones.getAttribute("data-product_variations");
  			if (!dataVariaciones) return [];
  			return JSON.parse(dataVariaciones);
		}
        const productos = document.querySelectorAll('li.product');
        productos.forEach(function(producto){
            producto.setAttribute('draggable', 'true');
            producto.addEventListener('dragstart', function(e){
                const nombre = producto.querySelector('.woocommerce-loop-product__title, h2, h3, .product-title')?.innerText || producto.innerText.trim();
                const imagen = producto.querySelector('img')?.src || '';
                const link = producto.querySelector('a.woocommerce-LoopProduct-link, a')?.href || '';
                let precioElemento = producto.querySelector('.price ins .woocommerce-Price-amount bdi');
				if (!precioElemento) {
  				precioElemento = producto.querySelector('.price .woocommerce-Price-amount bdi');
				}
				const precioTexto = precioElemento ? precioElemento.innerText : '0';
                const precioNumero = limpiarPrecio(precioTexto);
				const idProducto = obtenerIdProductoDesdeClase(producto);
				productoActual = {
    				nombre: nombre,
    				imagen: imagen,
    				link: link,
    				precio: precioNumero,
    				precioTexto: precioTexto,
    				cantidad: 1,
    				codigo: "",
    				descripcion: "",
    				id: idProducto
					};
				if (idProducto) {
    				fetch(`/wp-json/wc/store/v1/products/${idProducto}`)
        				.then(res => res.json())
        				.then(data => {
						productoActual.codigo = data.sku || "";
						productoActual.descripcion = limpiarHTML(data.short_description || "");
						productoActual.esVariable = data.type === "variable" || data.has_options === true;
						if (productoActual.esVariable) {
  							obtenerVariacionesDesdePagina(productoActual.link)
    							.then(variaciones => {
      								productoActual.variaciones = variaciones || [];
    							})
    							.catch(error => {
      							console.log("No se pudieron cargar variaciones", error);
      							productoActual.variaciones = [];
    							});
						}
        				})
        				.catch(error => {
            				console.log("No se pudo obtener SKU/descripción", error);
        				});
				}
                e.dataTransfer.setData('text/plain', nombre);
                panel.classList.add('tg-abierto');
            });
            producto.addEventListener('dragend', function(){
                setTimeout(function(){
                    if (!preview.classList.contains('tg-tiene-producto')) {
                        panel.classList.remove('tg-abierto');
                    }
                }, 500);
            });
        });
        dropArea.addEventListener('dragover', function(e){
            e.preventDefault();
            dropArea.classList.add('tg-drop-hover');
        });
        dropArea.addEventListener('dragleave', function(){
            dropArea.classList.remove('tg-drop-hover');
        });
        dropArea.addEventListener('drop', function(e){
            e.preventDefault();
            dropArea.classList.remove('tg-drop-hover');
            if (!productoActual) {
                return;
            }
            previewImg.src = productoActual.imagen;
            previewNombre.innerText = productoActual.nombre;
            defaultContent.style.display = 'none';
            preview.style.display = 'block';
            preview.classList.add('tg-tiene-producto');
            panel.classList.add('tg-abierto');

			if (productoActual.esVariable) {
  				agregarBtn.style.display = "none";
  				previewVariaciones.innerHTML = "<strong>Cargando opciones...</strong>";
  				obtenerVariacionesDesdePagina(productoActual.link)
    			.then(function(variaciones) {
      			productoActual.variaciones = variaciones || [];
      			mostrarOpcionesVariacion();
    			})
    			.catch(function(error) {
      			console.log("No se pudieron cargar variaciones", error);
      			previewVariaciones.innerHTML = "No se pudieron cargar las opciones.";
    			});
				} else {
  					agregarBtn.style.display = "block";
  					previewVariaciones.innerHTML = "";
				}
        });
removeBtn.addEventListener('click', function(e){
    e.preventDefault();
    e.stopPropagation();
    productoActual = null;
    previewImg.src = '';
    previewNombre.innerText = '';
    previewVariaciones.innerHTML = '';
    preview.style.display = 'none';
    preview.classList.remove('tg-tiene-producto');
    defaultContent.style.display = 'block';
    agregarBtn.innerText = 'Agregar al cotizador';
	agregarBtn.style.display = "block";
    panel.classList.remove('tg-abierto');
});
		function mostrarOpcionesVariacion() {
  			if (!productoActual || !productoActual.variaciones || !productoActual.variaciones.length) {
    			previewVariaciones.innerHTML = "";
    			return;
  			}
  			previewVariaciones.innerHTML = "<strong>Selecciona una opción:</strong>";
  			productoActual.variaciones.forEach(function(variacion) {
    			const precioVariacion = Number(variacion.display_price) || 0;
    		let nombreVariacion = Object.values(variacion.attributes || {})
      			.map(valor => String(valor).replace(/-/g, " "))
      			.join(" - ");
    			const boton = document.createElement("button");
    			boton.type = "button";
    			boton.style.width = "100%";
    			boton.style.marginTop = "8px";
    			boton.style.padding = "8px";
    			boton.style.background = "#fff200";
    			boton.style.border = "1px solid #000";
    			boton.style.fontWeight = "bold";
    			boton.style.cursor = "pointer";
    			boton.innerText = `${nombreVariacion} - ${formatoCOP(precioVariacion)}`;

    		boton.addEventListener("click", function() {
      			const productoSeleccionado = {
        			nombre: `${productoActual.nombre} - ${nombreVariacion}`,
        			imagen: productoActual.imagen,
        			link: productoActual.link + "?variation_id=" + variacion.variation_id,
        			precio: precioVariacion,
        			precioTexto: formatoCOP(precioVariacion),
        			cantidad: 1,
        			codigo: variacion.sku || "",
        			descripcion: productoActual.descripcion || ""
      				};
      			guardarProductoCotizador(productoSeleccionado);
    		});
    		previewVariaciones.appendChild(boton);
  			});
		}

			agregarBtn.addEventListener('click', function(e){
  				e.preventDefault();
  				e.stopPropagation();
  				if (!productoActual) return;
  					if (productoActual.esVariable) {
    					mostrarOpcionesVariacion();
    					return;
  					}
  				guardarProductoCotizador(productoActual);

			});

		function guardarProductoCotizador(producto) {
  			let productosCotizador = JSON.parse(localStorage.getItem('tg_productos_cotizador')) || [];
  			const existe = productosCotizador.some(function(item){
    			return item.link === producto.link;
  			});
  			if (!existe) {
    			productosCotizador.push(producto);
    			localStorage.setItem('tg_productos_cotizador', JSON.stringify(productosCotizador));
  			}
  			agregarBtn.innerText = 'Producto agregado';
  			setTimeout(function(){
    			productoActual = null;
    			previewImg.src = '';
    			previewNombre.innerText = '';
    			previewVariaciones.innerHTML = '';
    			preview.style.display = 'none';
    			preview.classList.remove('tg-tiene-producto');
    			defaultContent.style.display = 'block';
    			agregarBtn.innerText = 'Agregar al cotizador';
    			panel.classList.remove('tg-abierto');
  			 }, 800);
			}
    });
    </script>
    <?php
}
