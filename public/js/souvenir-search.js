/**
 * Datos de souvenirs para el buscador
 * El buscador se inicializa desde search.js
 */
function getDefaultSouvenirs() {
  return [
    { image: '/images/vela-osito/main.webp', hoverImage: '/images/vela-osito/hover.webp', name: 'Osito Souvenir', price: '$1900', stock: true, destacado: true, slug: 'vela-osito-souvenir', descripcion: 'Pequeños ositos de cera. Perfectos como souvenir para eventos y bautismos.', categoria: 'souvenirs' },
    { image: '/images/osito-capucha/main.webp', hoverImage: '/images/osito-capucha/hover.webp', name: 'Osito con Capucha', price: '$2300', stock: true, slug: 'osito-capucha', categoria: 'souvenirs' },
    { image: '/images/vela-cactus/main.webp', hoverImage: '/images/vela-cactus/hover.webp', name: 'Cactus 6cm', price: '$2200', stock: true, slug: 'vela-cactus-5cm', categoria: 'souvenirs' },
    { image: '/images/vela-cactus/main.webp', hoverImage: '/images/vela-cactus/hover.webp', name: 'Cactus 7cm', price: '$2300', stock: true, slug: 'vela-cactus-7cm', categoria: 'souvenirs' },
    { image: '/images/vela-jirafa/main.webp', hoverImage: '/images/vela-jirafa/hover.webp', name: 'Jirafa Souvenir', price: '$2400', stock: true, slug: 'vela-jirafa-souvenir', categoria: 'souvenirs' },
    { image: '/images/vela-leoncito/main.webp', hoverImage: '/images/vela-leoncito/hover.webp', name: 'Leoncito Souvenir', price: '$2200', stock: true, slug: 'vela-leoncito-souvenir', categoria: 'souvenirs' },
    { image: '/images/souvenir-elefantito/main.webp', hoverImage: '/images/souvenir-elefantito/hover.webp', name: 'Elefantito Souvenir', price: '$2500', stock: true, slug: 'vela-elefantito-souvenir', categoria: 'souvenirs' },
    { image: '/images/souvenir-elefantito-gris/main.webp', hoverImage: '/images/souvenir-elefantito-gris/hover.webp', name: 'Elefantito Gris Souvenir + base de madera', price: '$2800', stock: true, slug: 'vela-elefantito-gris-souvenir', categoria: 'souvenirs' },
    { image: '/images/souvenir-conejita-corazon/main.webp', hoverImage: '/images/souvenir-conejita-corazon/hover.webp', name: 'Conejita Souvenir', price: '$2500', stock: true, slug: 'vela-conejita-souvenir', categoria: 'souvenirs' },
    { image: '/images/vela-oso-pooh/main.webp', hoverImage: '/images/vela-oso-pooh/hover.webp', name: 'Oso Pooh Souvenir', price: '$2200', stock: true, slug: 'vela-oso-pooh', categoria: 'souvenirs' },
    { image: '/images/souvenir-vaca/main.webp', hoverImage: '/images/souvenir-vaca/hover.webp', name: 'Vaca Souvenir', price: '$2500', stock: true, slug: 'souvenir-vaca', categoria: 'souvenirs' },
    { image: '/images/vela-dinosaurio-souvenir/main.webp', name: 'Dinosaurio Souvenir', price: '$2500', stock: true, slug: 'vela-dinosaurio-souvenir', categoria: 'souvenirs' },
    { image: '/images/figuras-marinas-souvenir/main.webp', name: 'Figuras Marinas Souvenir', price: '$2000', stock: true, slug: 'figuras-marinas-souvenir', categoria: 'souvenirs' },
    { image: '/images/vela-pelota-souvenir/main.webp', name: 'Pelota Souvenir', price: '$2200', stock: true, slug: 'vela-pelota-souvenir', categoria: 'souvenirs' },
    { image: '/images/souvenir-abeja/main.webp', name: 'Abeja Souvenir', price: '$2500', stock: true, slug: 'vela-abeja-souvenir', categoria: 'souvenirs' },
    { image: '/images/souvenir-mariposa/main.webp', name: 'Mariposa Souvenir', price: '$2100', stock: true, slug: 'mariposa-souvenir', categoria: 'souvenirs' },
    { image: '/images/souvenir-mariposa-flores/main.webp', name: 'Mariposa Floral Souvenir', price: '$1600', stock: true, slug: 'mariposa-flores-souvenir', categoria: 'souvenirs' },
    { image: '/images/souvenir-mariposa-angel/main.webp', name: 'Mariposa Angel Souvenir', price: '$1800', stock: true, slug: 'mariposa-angel-souvenir', categoria: 'souvenirs' },
    { image: '/images/souvenir-bebe/main.webp', hoverImage: '/images/souvenir-bebe/hover.webp', name: 'Bebe Souvenir', price: '$2400', stock: true, slug: 'vela-bebe-souvenir', categoria: 'souvenirs' },
    { image: '/images/souvenir-angelito-2/main.webp', hoverImage: '/images/souvenir-angelito-2/hover.webp', name: 'Souvenir Angel nena/nene', price: '$2600', stock: true, slug: 'souvenir-angel-nena-nene', categoria: 'souvenirs' },
    { image: '/images/vela-angelito-souvenir/main.webp', hoverImage: '/images/vela-angelito-souvenir/hover.webp', name: 'Angelito Souvenir', price: '$2400', stock: true, slug: 'vela-angelito-souvenir', categoria: 'souvenirs' },
    { image: '/images/vela-bubble/main.webp', hoverImage: '/images/vela-bubble/hover.webp', name: 'Souvenir Bubble Simple', price: '$2100', stock: true, slug: 'vela-bubble-sin-caja', categoria: 'souvenirs' },
    { image: '/images/vela-bubble-en-caja/main.webp', hoverImage: '/images/vela-bubble-en-caja/hover.webp', name: 'Souvenir Bubble en caja', price: '$4700', stock: true, slug: 'vela-bubble-en-caja', categoria: 'souvenirs' },
    { image: '/images/vela-mini-bubble/main.webp', hoverImage: '/images/vela-mini-bubble/hover.webp', name: 'Mini Bubble Souvenir Pack', price: '$2400', stock: true, slug: 'vela-mini-bubble-souvenir-pack', categoria: 'souvenirs' },
    { image: '/images/vela-nube/main.webp', hoverImage: '/images/vela-nube/hover.webp', name: 'Nube Souvenir', price: '$2000', stock: true, slug: 'vela-nube', categoria: 'souvenirs' },
    { image: '/images/vela-flores-en-caja/main.webp', hoverImage: '/images/vela-flores-en-caja/hover.webp', name: 'Souvenir Flores en caja', price: '$3600', stock: true, slug: 'vela-flores-en-caja', categoria: 'souvenirs' },
    { image: '/images/velas-flor-loto/main.webp', name: 'Flor Loto', price: '$2400', stock: true, slug: 'velas-flor-lotojpg', categoria: 'souvenirs' },
    { image: '/images/vela-flor-peonia/main.webp', hoverImage: '/images/vela-flor-peonia/hover.webp', name: 'Flor Peonia', price: '$1900', stock: true, slug: 'vela-flor-peonia', categoria: 'souvenirs' },
    { image: '/images/Rosas-souvenir/main.webp', hoverImage: '/images/Rosas-souvenir/hover.webp', name: 'Rosas Souvenir', price: '$1900', stock: true, slug: 'vela-rosa-souvenir', categoria: 'souvenirs' },
    { image: '/images/clavelina-souvenir/main.webp', name: 'Clavelina Souvenir', price: '$2000', stock: true, slug: 'vela-clavelina-souvenir', categoria: 'souvenirs' },
    { image: '/images/margaritas-souvenir/main.webp', hoverImage: '/images/margaritas-souvenir/hover.webp', name: 'Margaritas Souvenir', price: '$1700', stock: true, slug: 'vela-margaritas-souvenir', categoria: 'souvenirs' },
    { image: '/images/corazon-souvenir/main.webp', hoverImage: '/images/corazon-souvenir/hover.webp', name: 'Corazon Souvenir', price: '$2400', stock: true, slug: 'vela-corazon-souvenir', categoria: 'souvenirs' },
    { image: '/images/oso-corazon-souvenir/main.webp', name: 'Oso Corazon Souvenir', price: '$2400', stock: true, slug: 'vela-oso-corazon-souvenir', categoria: 'souvenirs' },
    { image: '/images/oso-panzon/main.webp', hoverImage: '/images/oso-panzon/hover.webp', name: 'Oso Panzon Souvenir', price: '$2400', stock: true, slug: 'vela-oso-panzon', categoria: 'souvenirs' },
    { image: '/images/souvenir-envase100cc/main.webp', hoverImage: '/images/souvenir-envase100cc/hover.webp', name: 'Souvenir en envase 100cc', price: '$4600', stock: true, slug: 'vela-souvenir-envase100cc', categoria: 'souvenirs' },
    { image: '/images/souvenir-envase-40cc/main.webp', hoverImage: '/images/souvenir-envase-40cc/hover.webp', name: 'Souvenir en envase 40cc', price: '$3600', stock: true, slug: 'vela-souvenir-envase40cc', categoria: 'souvenirs' },
    { image: '/images/souvenir-caramelera/main.webp', name: 'Souvenir caramelera sin tapa', price: '$4000', stock: true, slug: 'vela-souvenir-caramelera-sin-tapa', categoria: 'souvenirs' },
    { image: '/images/souvenir-caramelera-con-tapa/main.webp', name: 'Souvenir caramelera con tapa', price: '$5000', stock: true, slug: 'vela-souvenir-caramelera-con-tapa', categoria: 'souvenirs' }
  ];
}
