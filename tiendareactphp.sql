CREATE USER 'reactphp'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON tiendareactphp.* TO 'reactphp'@'localhost';
FLUSH PRIVILEGES;

CREATE DATABASE IF NOT EXISTS `tiendareactphp`;
USE `tiendareactphp`;

DROP TABLE IF EXISTS `productos`;
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

INSERT INTO `productos` (`id`, `nombre`, `precio`) VALUES
(1, 'Auriculares Bluetooth', 199.99),
(2, 'Mouse Gamer RGB', 29.50),
(3, 'Teclado Mecánico', 89.00),
(4, 'Monitor 24 pulgadas', 195.00),
(5, 'Silla Ergonómica', 150.75),
(6, 'Laptop Gamer', 1300.50),
(7, 'Laptop', 13.50),
(8, 'Celular XII', 550.00);