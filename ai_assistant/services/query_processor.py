"""
Procesador de consultas API para SistemaAdmin AI
Convierte intenciones en consultas API específicas
"""

import logging
from typing import Dict, List, Any, Optional

logger = logging.getLogger(__name__)

class QueryProcessor:
    """Procesador que convierte intenciones en consultas API"""
    
    def __init__(self, api_client):
        self.api = api_client
    
    def generate_api_query(self, intent: str, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta API basada en intención y entidades"""
        try:
            logger.info(f"🔍 Generando consulta API para intención: {intent}")
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
            logger.error(f"❌ Error generando consulta API: {e}")
            return {'action': 'estudiantes'}  # Consulta de respaldo
    
    def _generate_list_students_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta para listar estudiantes"""
        query = {'action': 'estudiantes'}
        
        # Filtrar por curso si se especifica
        if "curso" in entities:
            curso = entities["curso"]
            if curso.get("anio") and curso.get("division"):
                # Buscar curso específico primero
                cursos = self.api.get_cursos()
                if cursos.get('success'):
                    for curso_data in cursos.get('data', []):
                        if (curso_data.get('anio') == int(curso.get("anio")) and 
                            curso_data.get('division') == curso.get("division")):
                            query['curso_id'] = curso_data['id']
                            break
        
        return query
    
    def _generate_search_student_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta para buscar estudiante específico"""
        if "nombre" in entities:
            return {
                'action': 'buscar_estudiantes',
                'search': entities["nombre"]
            }
        
        return {'action': 'estudiantes'}
    
    def _generate_student_grades_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta para notas de estudiante"""
        if "nombre" in entities:
            # Primero buscar el estudiante
            estudiantes = self.api.buscar_estudiantes(entities["nombre"])
            if estudiantes.get('success') and estudiantes.get('data'):
                student_id = estudiantes['data'][0]['id']
                return {
                    'action': 'notas_estudiante',
                    'estudiante_id': student_id
                }
        
        return {'action': 'estudiantes'}
    
    def _generate_course_stats_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta para estadísticas de curso"""
        if "curso" in entities:
            curso = entities["curso"]
            if curso.get("anio") and curso.get("division"):
                # Buscar curso específico
                cursos = self.api.get_cursos()
                if cursos.get('success'):
                    for curso_data in cursos.get('data', []):
                        if (curso_data.get('anio') == int(curso.get("anio")) and 
                            curso_data.get('division') == curso.get("division")):
                            return {
                                'action': 'estadisticas_curso',
                                'curso_id': curso_data['id']
                            }
        
        return {'action': 'estadisticas'}
    
    def _generate_attention_calls_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta para llamados de atención"""
        return {'action': 'llamados'}
    
    def _generate_teachers_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta para profesores"""
        return {'action': 'profesores'}
    
    def _generate_schedules_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta para horarios"""
        return {'action': 'horarios'}
    
    def _generate_subjects_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta para materias"""
        return {'action': 'materias'}
    
    def _generate_general_stats_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta para estadísticas generales"""
        return {'action': 'estadisticas'}
    
    def _generate_reports_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta para reportes"""
        if "asistencia" in str(entities).lower():
            return {'action': 'reporte_asistencia'}
        else:
            return {'action': 'estadisticas'}
    
    def _generate_fallback_query(self, entities: Dict[str, Any]) -> Dict[str, Any]:
        """Generar consulta de respaldo"""
        logger.warning("⚠️ Intención no reconocida, usando consulta de respaldo")
        return {'action': 'estudiantes'}
    
    def validate_query(self, query: Dict[str, Any]) -> bool:
        """Validar que la consulta API sea segura"""
        # Validar que la acción esté permitida
        allowed_actions = [
            'estudiantes', 'estudiante', 'estudiantes_curso', 'buscar_estudiantes',
            'notas_estudiante', 'profesores', 'cursos', 'materias', 'especialidades',
            'llamados', 'horarios', 'estadisticas', 'estadisticas_curso',
            'reporte_asistencia', 'health'
        ]
        
        action = query.get('action', '')
        if action not in allowed_actions:
            logger.warning(f"⚠️ Acción no permitida: {action}")
            return False
        
        return True
    
    def optimize_query(self, query: Dict[str, Any]) -> Dict[str, Any]:
        """Optimizar consulta para mejor rendimiento"""
        # Agregar límites por defecto para consultas grandes
        if query.get('action') in ['estudiantes', 'profesores', 'llamados']:
            if 'limit' not in query:
                query['limit'] = 50
        
        return query
