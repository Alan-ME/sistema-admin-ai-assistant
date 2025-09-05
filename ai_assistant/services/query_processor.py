"""
Procesador de consultas SQL para SistemaAdmin AI
Convierte intenciones en consultas SQL específicas
"""

import logging
from typing import Dict, List, Any, Optional
from database.queries import SistemaAdminQueries

logger = logging.getLogger(__name__)

class QueryProcessor:
    """Procesador que convierte intenciones en consultas SQL"""
    
    def __init__(self, db_connection):
        self.db = db_connection
        self.queries = SistemaAdminQueries()
    
    def generate_sql_query(self, intent: str, entities: Dict[str, Any]) -> str:
        """Generar consulta SQL basada en intención y entidades"""
        try:
            logger.info(f"🔍 Generando consulta SQL para intención: {intent}")
            logger.info(f"🏷️ Entidades: {entities}")
            
            if intent == "listar_estudiantes":
                return self._generate_list_students_query(entities)
            elif intent == "buscar_estudiante":
                return self._generate_search_student_query(entities)
            elif intent == "notas_estudiante":
                return self._generate_student_grades_query(entities)
            elif intent == "estadisticas_curso":
                return self._generate_course_stats_query(entities)
            elif intent == "llamados_atencion":
                return self._generate_attention_calls_query(entities)
            elif intent == "profesores":
                return self._generate_teachers_query(entities)
            elif intent == "horarios":
                return self._generate_schedules_query(entities)
            elif intent == "materias":
                return self._generate_subjects_query(entities)
            elif intent == "estadisticas_generales":
                return self._generate_general_stats_query(entities)
            elif intent == "reportes":
                return self._generate_reports_query(entities)
            else:
                return self._generate_fallback_query(entities)
                
        except Exception as e:
            logger.error(f"❌ Error generando consulta SQL: {e}")
            return self.queries.GET_ALL_STUDENTS  # Consulta de respaldo
    
    def _generate_list_students_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta para listar estudiantes"""
        base_query = self.queries.GET_ALL_STUDENTS
        
        # Filtrar por curso si se especifica
        if "curso" in entities:
            curso = entities["curso"]
            if curso.get("anio") and curso.get("division"):
                # Buscar curso específico
                curso_query = """
                SELECT id FROM cursos 
                WHERE anio = %s AND division = %s AND activo = 1
                """
                curso_result = self.db.execute_query(curso_query, (curso["anio"], curso["division"]))
                if curso_result:
                    curso_id = curso_result[0]["id"]
                    return f"""
                    {base_query}
                    AND e.curso_id = {curso_id}
                    """
        
        # Filtrar por especialidad si se especifica
        if "especialidad" in entities:
            especialidad = entities["especialidad"]
            return f"""
            {base_query}
            AND esp.nombre LIKE '%{especialidad}%'
            """
        
        return base_query
    
    def _generate_search_student_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta para buscar estudiante específico"""
        if "nombre" in entities:
            nombre = entities["nombre"]
            return f"""
            {self.queries.GET_ALL_STUDENTS}
            AND (e.nombre LIKE '%{nombre}%' OR e.apellido LIKE '%{nombre}%')
            LIMIT 10
            """
        
        return self.queries.GET_ALL_STUDENTS + " LIMIT 10"
    
    def _generate_student_grades_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta para notas de estudiante"""
        if "nombre" in entities:
            nombre = entities["nombre"]
            # Primero buscar el estudiante
            student_query = f"""
            SELECT id FROM estudiantes 
            WHERE (nombre LIKE '%{nombre}%' OR apellido LIKE '%{nombre}%') 
            AND activo = 1 
            LIMIT 1
            """
            student_result = self.db.execute_query(student_query)
            
            if student_result:
                student_id = student_result[0]["id"]
                return f"""
                {self.queries.GET_STUDENT_GRADES}
                AND e.id = {student_id}
                """
        
        return self.queries.GET_STUDENT_GRADES + " LIMIT 50"
    
    def _generate_course_stats_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta para estadísticas de curso"""
        if "curso" in entities:
            curso = entities["curso"]
            if curso.get("anio") and curso.get("division"):
                # Buscar curso específico
                curso_query = """
                SELECT id FROM cursos 
                WHERE anio = %s AND division = %s AND activo = 1
                """
                curso_result = self.db.execute_query(curso_query, (curso["anio"], curso["division"]))
                if curso_result:
                    curso_id = curso_result[0]["id"]
                    return f"""
                    {self.queries.GET_AVERAGE_GRADES}
                    AND c.id = {curso_id}
                    """
        
        return self.queries.GET_COURSE_STATISTICS
    
    def _generate_attention_calls_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta para llamados de atención"""
        if "nombre" in entities:
            nombre = entities["nombre"]
            return f"""
            {self.queries.GET_ATTENTION_CALLS}
            AND (e.nombre LIKE '%{nombre}%' OR e.apellido LIKE '%{nombre}%')
            """
        
        return self.queries.GET_ATTENTION_CALLS
    
    def _generate_teachers_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta para profesores"""
        if "materia" in entities:
            materia = entities["materia"]
            return f"""
            {self.queries.GET_ALL_TEACHERS}
            AND p.id IN (
                SELECT DISTINCT pm.profesor_id 
                FROM profesor_materia pm 
                JOIN materias m ON pm.materia_id = m.id 
                WHERE m.nombre LIKE '%{materia}%'
            )
            """
        
        return self.queries.GET_ALL_TEACHERS
    
    def _generate_schedules_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta para horarios"""
        if "curso" in entities:
            curso = entities["curso"]
            if curso.get("anio") and curso.get("division"):
                # Buscar curso específico
                curso_query = """
                SELECT id FROM cursos 
                WHERE anio = %s AND division = %s AND activo = 1
                """
                curso_result = self.db.execute_query(curso_query, (curso["anio"], curso["division"]))
                if curso_result:
                    curso_id = curso_result[0]["id"]
                    return f"""
                    {self.queries.GET_SCHEDULES}
                    AND h.curso_id = {curso_id}
                    """
        
        return self.queries.GET_SCHEDULES
    
    def _generate_subjects_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta para materias"""
        if "especialidad" in entities:
            especialidad = entities["especialidad"]
            return f"""
            {self.queries.GET_ALL_SUBJECTS}
            AND esp.nombre LIKE '%{especialidad}%'
            """
        
        return self.queries.GET_ALL_SUBJECTS
    
    def _generate_general_stats_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta para estadísticas generales"""
        return self.queries.GET_STATISTICS
    
    def _generate_reports_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta para reportes"""
        if "asistencia" in str(entities).lower():
            return self.queries.GET_ATTENDANCE_REPORT
        elif "notas" in str(entities).lower() or "calificaciones" in str(entities).lower():
            return self.queries.GET_GRADE_DISTRIBUTION
        else:
            return self.queries.GET_COURSE_STATISTICS
    
    def _generate_fallback_query(self, entities: Dict[str, Any]) -> str:
        """Generar consulta de respaldo"""
        logger.warning("⚠️ Intención no reconocida, usando consulta de respaldo")
        return self.queries.GET_ALL_STUDENTS + " LIMIT 20"
    
    def validate_query(self, query: str) -> bool:
        """Validar que la consulta SQL sea segura"""
        # Lista de palabras peligrosas
        dangerous_keywords = [
            'DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE',
            'TRUNCATE', 'EXEC', 'EXECUTE', 'UNION', '--', '/*', '*/'
        ]
        
        query_upper = query.upper()
        for keyword in dangerous_keywords:
            if keyword in query_upper:
                logger.warning(f"⚠️ Consulta potencialmente peligrosa detectada: {keyword}")
                return False
        
        return True
    
    def optimize_query(self, query: str) -> str:
        """Optimizar consulta para mejor rendimiento"""
        # Agregar LIMIT si no existe
        if 'LIMIT' not in query.upper():
            query += ' LIMIT 100'
        
        # Optimizar para plan gratuito de Render
        query = query.replace('SELECT *', 'SELECT id, nombre, apellido, email')
        
        return query
