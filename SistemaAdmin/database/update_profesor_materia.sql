-- Script para agregar la columna curso_id a la tabla profesor_materia
-- y actualizar las restricciones necesarias

-- 1. Agregar la columna curso_id
ALTER TABLE `profesor_materia` 
ADD COLUMN `curso_id` int(11) DEFAULT NULL AFTER `materia_id`;

-- 2. Agregar índice para la nueva columna
ALTER TABLE `profesor_materia` 
ADD KEY `idx_curso_id` (`curso_id`);

-- 3. Agregar restricción de clave foránea
ALTER TABLE `profesor_materia`
ADD CONSTRAINT `profesor_materia_ibfk_3` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

-- 4. Eliminar la restricción única anterior que no incluía curso_id
ALTER TABLE `profesor_materia` 
DROP INDEX `uk_profesor_materia_anio`;

-- 5. Agregar nueva restricción única que incluye curso_id
ALTER TABLE `profesor_materia` 
ADD UNIQUE KEY `uk_profesor_materia_curso_anio` (`profesor_id`, `materia_id`, `curso_id`, `anio_academico`);

-- 6. Opcional: Actualizar datos existentes (puedes comentar esto si no tienes datos de prueba)
-- UPDATE profesor_materia pm 
-- JOIN profesor_curso pc ON pm.profesor_id = pc.profesor_id AND pm.anio_academico = pc.anio_academico 
-- SET pm.curso_id = pc.curso_id 
-- WHERE pm.curso_id IS NULL AND pc.activo = 1;
