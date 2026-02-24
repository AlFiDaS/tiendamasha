/**
 * Datos de productos para el buscador
 * El buscador se inicializa desde search.js
 */
function getDefaultProductos() {
  return [
    { image: '/images/vela-xoxo/main.webp', hoverImage: '/images/vela-xoxo/hover.webp', name: 'Vela XOXO', price: '$15900', stock: true, destacado: true, slug: 'vela-xoxo', categoria: 'productos' },
    { image: '/images/vela-cuenco-con-mariposa/main.webp', name: 'Cuenco con mariposa. Coleccion XOXO', price: '$9000', stock: true, slug: 'vela-cuenco-con-mariposa', categoria: 'productos' },
    { image: '/images/vela-bubble-con-base-bubble/main.webp', name: 'Vela bubble con base bubble. Coleccion XOXO', price: '$9900', stock: true, slug: 'vela-bubble-con-base-bubble', categoria: 'productos' },
    { image: '/images/ramo-flores-7/main.webp', name: 'Ramo con 7 flores. Coleccion XOXO', price: '$14900', stock: true, destacado: true, slug: 'ramo-flores-7', categoria: 'productos' },
    { image: '/images/vela-aromatica/main.webp', hoverImage: '/images/vela-aromatica/hover.webp', name: 'Vela Aromatica', price: '$10900', stock: true, slug: 'vela-aromatica', categoria: 'productos' },
    { image: '/images/vela-iced-coffe/hover.webp', hoverImage: '/images/vela-iced-coffe/main.webp', name: 'Iced Coffee', price: '$17900', stock: true, slug: 'velas-iced-coffe', categoria: 'productos' },
    { image: '/images/vela-lavanda-tea/hover.webp', hoverImage: '/images/vela-lavanda-tea/main.webp', name: 'Lavanda Tea', price: '$17900', stock: true, slug: 'velas-lavanda-tea', categoria: 'productos' },
    { image: '/images/vela-strawberry-smothie/hover.webp', hoverImage: '/images/vela-strawberry-smothie/main.webp', name: 'Strawberry Smothie', price: '$17900', stock: true, slug: 'velas-strawberry-smothie', categoria: 'productos' },
    { image: '/images/Set-elegance/main.webp', name: 'Set Elegance', price: '$29900', stock: true, slug: 'set-elegance', categoria: 'productos' },
    { image: '/images/elegance-18cm/main.webp', name: 'Elegance 18cm', price: '$16900', stock: true, slug: 'elegance-18cm', categoria: 'productos' },
    { image: '/images/elegance-11cm/main.webp', name: 'Elegance 11cm', price: '$7900', stock: true, slug: 'elegance-11cm', categoria: 'productos' },
    { image: '/images/elegance-7cm/main.webp', name: 'Elegance 7cm', price: '$6900', stock: true, slug: 'elegance-7cm', categoria: 'productos' },
    { image: '/images/velon-bubble/main.webp', name: 'Velon Bubble', price: '$7900', stock: true, slug: 'velon-bubble', categoria: 'productos' },
    { image: '/images/tornasol/main.webp', hoverImage: '/images/tornasol/hover.webp', name: 'Vela tornasol', price: '$16900', stock: true, slug: 'tornasol', categoria: 'productos' },
    { image: '/images/tornasol-ondulada/main.webp', hoverImage: '/images/tornasol-ondulada/hover.webp', name: 'Vela tornasol ondulada', price: '$16900', stock: true, slug: 'tornasol-ondulada', categoria: 'productos' },
    { image: '/images/vela-amore/main.webp', hoverImage: '/images/vela-amore/hover.webp', name: 'Velas Amore', price: '$16900', stock: true, slug: 'velas-amore', categoria: 'productos' },
    { image: '/images/vela-amore-mini/main.webp', name: 'Velas Amore Oval', price: '$14900', stock: true, slug: 'velas-amore-oval', categoria: 'productos' },
    { image: '/images/duo-osito/main.webp', hoverImage: '/images/duo-osito/hover.webp', name: 'Duo Capsula Love Osito', price: '$15500', stock: true, slug: 'duo-capsula-osito', categoria: 'productos' },
    { image: '/images/duo-corazon/main.webp', hoverImage: '/images/duo-corazon/hover.webp', name: 'Duo Capsula Love Corazon', price: '$15500', stock: true, slug: 'duo-capsula-corazon', categoria: 'productos' },
    { image: '/images/set-velas-20cm-amore/main.webp', name: 'Set 3 Velas Amore de 20 cm', price: '$10500', stock: true, slug: 'set-velas-20cm-amore', categoria: 'productos' },
    { image: '/images/set-velas-20cm-verde/main.webp', name: 'Set 3 Velas Verde de 20 cm', price: '$10500', stock: true, slug: 'set-velas-20cm-verde', categoria: 'productos' },
    { image: '/images/velon-torsionado-20cm/main.webp', name: 'Velon Torsionado de 20cm', price: '$3900', stock: true, slug: 'velon-torsionado-20cm', categoria: 'productos' },
    { image: '/images/vela-portacandelabro/main.webp', hoverImage: '/images/vela-portacandelabro/hover.webp', name: 'Vela 20cm con Portacandelabro', price: '$9900', stock: true, slug: 'vela-portacandelabro', categoria: 'productos' },
    { image: '/images/vela-pearl/main.webp', name: 'Vela Pearl', price: '$9900', stock: true, slug: 'vela-pearl', categoria: 'productos' },
    { image: '/images/vela-pearl-mayor/main.webp', name: 'Vela Pearl por mayor', price: '$0', stock: true, slug: 'vela-pearl-mayor', categoria: 'productos' },
    { image: '/images/velas-set-aromaticas/main.webp', hoverImage: '/images/velas-set-aromaticas/hover.webp', name: 'Set de Velas Aromaticas', price: '$14900', stock: true, slug: 'vela-set-aromaticas', categoria: 'productos' },
    { image: '/images/vela-cocker/main.webp', name: 'Cocker Personalizado', price: '$8500', stock: true, slug: 'vela-cocker', categoria: 'productos' },
    { image: '/images/vela-labrador/main.webp', name: 'Labrador Personalizado', price: '$9500', stock: true, slug: 'vela-labrador', categoria: 'productos' },
    { image: '/images/vela-caniche/main.webp', hoverImage: '/images/vela-caniche/hover.webp', name: 'Caniche Personalizado', price: '$9500', stock: true, destacado: true, slug: 'vela-caniche', categoria: 'productos' },
    { image: '/images/vela-caniche-gris/main.webp', hoverImage: '/images/vela-caniche-gris/hover.webp', name: 'Caniche 2 Personalizado', price: '$8500', stock: true, slug: 'vela-caniche-2', categoria: 'productos' },
    { image: '/images/vela-yorkshire/main.webp', hoverImage: '/images/vela-yorkshire/hover.webp', name: 'Yorkshire Terrier Personalizado', price: '$8500', stock: true, slug: 'vela-yorkshire', categoria: 'productos' },
    { image: '/images/vela-golden/main.webp', hoverImage: '/images/vela-golden/hover.webp', name: 'Golden Personalizado', price: '$9500', stock: true, slug: 'vela-golden', categoria: 'productos' },
    { image: '/images/vela-rottwailer/main.webp', hoverImage: '/images/vela-rottwailer/hover.webp', name: 'Rottweiler Personalizado', price: '$9500', stock: true, slug: 'vela-rottweiler', categoria: 'productos' },
    { image: '/images/velas-gatito/main.webp', hoverImage: '/images/velas-gatito/hover.webp', name: 'Gatito Personalizado', price: '$8500', stock: true, destacado: true, slug: 'vela-gatito', categoria: 'productos' },
    { image: '/images/vela-salchicha/main.webp', hoverImage: '/images/vela-salchicha/hover.webp', name: 'Perro Salchicha', price: '$8500', stock: true, slug: 'vela-salchicha', categoria: 'productos' },
    { image: '/images/vela-terrier/main.webp', hoverImage: '/images/vela-terrier/hover.webp', name: 'Terrier Personalizado', price: '$9500', stock: true, slug: 'vela-terrier', categoria: 'productos' },
    { image: '/images/vela-bulldog-frances/main.webp', name: 'Bulldog Frances Personalizado', price: '$7500', stock: true, slug: 'vela-bulldog-frances', categoria: 'productos' },
    { image: '/images/vela-pug/main.webp', hoverImage: '/images/vela-pug/hover.webp', name: 'Pug Personalizado', price: '$7500', stock: true, slug: 'vela-pug', categoria: 'productos' },
    { image: '/images/bombones-aromaticos/main.webp', hoverImage: '/images/bombones-aromaticos/hover.webp', name: 'Bombones Aromaticos', price: '$4600', stock: true, slug: 'bombones-aromaticos', categoria: 'productos' }
  ];
}
