export function agregarAlCarrito(producto) {
    const key = 'carrito' + (window.__STORE_BASE || '');
    const carrito = JSON.parse(localStorage.getItem(key)) || [];
    carrito.push(producto);
    localStorage.setItem(key, JSON.stringify(carrito));
    alert(`Agregaste "${producto.name}" al carrito`);
  }
  