"""
Servicio de Procesamiento de Lenguaje Natural
Optimizado para plan gratuito de Render
"""

import os
import re
import logging
from typing import Dict, List, Tuple, Any
import json

logger = logging.getLogger(__name__)

class NLPService:
    """Servicio de procesamiento de lenguaje natural para consultas en español"""
    
    def __init__(self):
        self.intents = self._load_intents()
        self.entities = self._load_entities()
        self.responses = self._load_responses()
    
    def _load_intents(self) -> Dict[str, List[str]]:
        """Cargar patrones de intenciones"""
        return {
            "listar_estudiantes": [
                "estudiantes", "alumnos", "listar estudiantes", "mostrar estudiantes",
                "cuántos estudiantes", "todos los estudiantes", "ver estudiantes"
            ],
            "buscar_estudiante": [
                "buscar estudiante", "encontrar estudiante", "estudiante específico",
                "datos de", "información de", "ficha de"
            ],
            "notas_estudiante": [
                "notas de", "calificaciones de", "promedio de", "rendimiento de",
                "notas del estudiante", "calificaciones del alumno"
            ],
            "estadisticas_curso": [
                "estadísticas del curso", "promedio del curso", "rendimiento del curso",
                "curso", "año", "división", "estadísticas de"
            ],
            "llamados_atencion": [
                "llamados de atención", "amonestaciones", "sanciones", "disciplina",
                "problemas de conducta", "llamados"
            ],
            "profesores": [
                "profesores", "docentes", "maestros", "listar profesores",
                "profesor de", "docente de"
            ],
            "horarios": [
                "horarios", "horario de clases", "cuándo es", "qué día",
                "horario del curso", "clases de"
            ],
            "materias": [
                "materias", "asignaturas", "materia de", "asignatura de",
                "qué materias", "listar materias"
            ],
            "estadisticas_generales": [
                "estadísticas", "estadísticas generales", "resumen", "totales",
                "cuántos hay", "cantidad total", "resumen del sistema"
            ],
            "reportes": [
                "reporte", "reportes", "generar reporte", "informe",
                "análisis de", "estudio de"
            ]
        }
    
    def _load_entities(self) -> Dict[str, List[str]]:
        """Cargar patrones de entidades"""
        return {
            "cursos": [
                r"(\d+)\s*(?:°|º|grado|año)\s*([a-z])",  # 1° A, 2º B, etc.
                r"(\d+)\s*(?:°|º|grado|año)",  # 1°, 2º, etc.
                r"([a-z])\s*(?:°|º|grado|año)\s*(\d+)",  # A 1°, B 2º, etc.
            ],
            "especialidades": [
                "informática", "electromecánica", "construcciones", "química",
                "programación", "sistemas", "mecánica", "construcción"
            ],
            "materias": [
                "matemática", "matemáticas", "lengua", "literatura", "historia",
                "geografía", "física", "química", "biología", "inglés", "educación física",
                "taller", "prácticas", "tecnología"
            ],
            "turnos": [
                "mañana", "tarde", "contraturno", "vespertino", "matutino"
            ],
            "numeros": [
                r"\d+",  # Cualquier número
            ]
        }
    
    def _load_responses(self) -> Dict[str, str]:
        """Cargar plantillas de respuestas"""
        return {
            "estudiantes_encontrados": "Encontré {count} estudiantes en {location}",
            "estudiante_no_encontrado": "No encontré estudiantes que coincidan con tu búsqueda",
            "notas_estudiante": "Las notas de {student} son:",
            "estadisticas_curso": "Estadísticas del curso {course}:",
            "error_consulta": "Hubo un error procesando tu consulta. Intenta reformularla.",
            "sin_datos": "No hay datos disponibles para mostrar",
            "consulta_ambigua": "Tu consulta no es clara. ¿Podrías ser más específico?"
        }
    
    def process_question(self, question: str) -> Tuple[str, Dict[str, Any]]:
        """Procesar pregunta del usuario y extraer intención y entidades"""
        try:
            question_lower = question.lower().strip()
            
            # Detectar intención
            intent = self._detect_intent(question_lower)
            
            # Extraer entidades
            entities = self._extract_entities(question_lower)
            
            logger.info(f"🤖 Intención detectada: {intent}")
            logger.info(f"🏷️ Entidades extraídas: {entities}")
            
            return intent, entities
            
        except Exception as e:
            logger.error(f"❌ Error procesando pregunta: {e}")
            return "error", {}
    
    def _detect_intent(self, question: str) -> str:
        """Detectar la intención de la pregunta"""
        best_intent = "consulta_ambigua"
        max_matches = 0
        
        for intent, patterns in self.intents.items():
            matches = 0
            for pattern in patterns:
                if pattern in question:
                    matches += 1
            
            if matches > max_matches:
                max_matches = matches
                best_intent = intent
        
        return best_intent if max_matches > 0 else "consulta_ambigua"
    
    def _extract_entities(self, question: str) -> Dict[str, Any]:
        """Extraer entidades de la pregunta"""
        entities = {}
        
        # Extraer cursos
        curso_match = self._extract_course(question)
        if curso_match:
            entities["curso"] = curso_match
        
        # Extraer especialidades
        especialidad = self._extract_specialty(question)
        if especialidad:
            entities["especialidad"] = especialidad
        
        # Extraer materias
        materia = self._extract_subject(question)
        if materia:
            entities["materia"] = materia
        
        # Extraer turnos
        turno = self._extract_shift(question)
        if turno:
            entities["turno"] = turno
        
        # Extraer nombres (patrón simple)
        nombre = self._extract_name(question)
        if nombre:
            entities["nombre"] = nombre
        
        # Extraer números
        numeros = self._extract_numbers(question)
        if numeros:
            entities["numeros"] = numeros
        
        return entities
    
    def _extract_course(self, question: str) -> Dict[str, str]:
        """Extraer información de curso"""
        for pattern in self.entities["cursos"]:
            match = re.search(pattern, question)
            if match:
                groups = match.groups()
                if len(groups) == 2:
                    return {"anio": groups[0], "division": groups[1].upper()}
                elif len(groups) == 1:
                    return {"anio": groups[0], "division": None}
        return None
    
    def _extract_specialty(self, question: str) -> str:
        """Extraer especialidad"""
        for especialidad in self.entities["especialidades"]:
            if especialidad in question:
                return especialidad
        return None
    
    def _extract_subject(self, question: str) -> str:
        """Extraer materia"""
        for materia in self.entities["materias"]:
            if materia in question:
                return materia
        return None
    
    def _extract_shift(self, question: str) -> str:
        """Extraer turno"""
        for turno in self.entities["turnos"]:
            if turno in question:
                return turno
        return None
    
    def _extract_name(self, question: str) -> str:
        """Extraer nombre (patrón simple)"""
        # Buscar patrones como "de Juan", "Juan Pérez", etc.
        name_patterns = [
            r"de\s+([A-Za-záéíóúñ\s]+)",
            r"([A-Za-záéíóúñ]+(?:\s+[A-Za-záéíóúñ]+)*)\s+(?:tiene|tiene|es|está)"
        ]
        
        for pattern in name_patterns:
            match = re.search(pattern, question, re.IGNORECASE)
            if match:
                return match.group(1).strip()
        return None
    
    def _extract_numbers(self, question: str) -> List[int]:
        """Extraer números de la pregunta"""
        numbers = re.findall(r'\d+', question)
        return [int(n) for n in numbers]
    
    def generate_response(self, data: List[Dict], analysis: Dict, intent: str) -> str:
        """Generar respuesta natural basada en los datos"""
        try:
            if not data:
                return self.responses["sin_datos"]
            
            if intent == "listar_estudiantes":
                return self._generate_students_response(data)
            elif intent == "buscar_estudiante":
                return self._generate_student_search_response(data)
            elif intent == "notas_estudiante":
                return self._generate_grades_response(data)
            elif intent == "estadisticas_curso":
                return self._generate_course_stats_response(data, analysis)
            elif intent == "llamados_atencion":
                return self._generate_attention_calls_response(data)
            elif intent == "profesores":
                return self._generate_teachers_response(data)
            elif intent == "estadisticas_generales":
                return self._generate_general_stats_response(data)
            else:
                return self._generate_generic_response(data)
                
        except Exception as e:
            logger.error(f"❌ Error generando respuesta: {e}")
            return self.responses["error_consulta"]
    
    def _generate_students_response(self, data: List[Dict]) -> str:
        """Generar respuesta para lista de estudiantes"""
        count = len(data)
        if count == 0:
            return self.responses["estudiante_no_encontrado"]
        
        response = f"Encontré {count} estudiantes:\n\n"
        
        for i, student in enumerate(data[:10], 1):  # Mostrar máximo 10
            nombre = f"{student.get('nombre', '')} {student.get('apellido', '')}"
            curso = f"{student.get('anio', '')}° {student.get('division', '')}"
            response += f"{i}. {nombre} - {curso}\n"
        
        if count > 10:
            response += f"\n... y {count - 10} estudiantes más."
        
        return response
    
    def _generate_student_search_response(self, data: List[Dict]) -> str:
        """Generar respuesta para búsqueda de estudiante"""
        if not data:
            return "No encontré estudiantes que coincidan con tu búsqueda."
        
        student = data[0]
        nombre = f"{student.get('nombre', '')} {student.get('apellido', '')}"
        curso = f"{student.get('anio', '')}° {student.get('division', '')}"
        
        response = f"Datos de {nombre}:\n"
        response += f"• Curso: {curso}\n"
        response += f"• DNI: {student.get('dni', 'N/A')}\n"
        response += f"• Email: {student.get('email', 'N/A')}\n"
        response += f"• Teléfono: {student.get('telefono_celular', 'N/A')}\n"
        
        return response
    
    def _generate_grades_response(self, data: List[Dict]) -> str:
        """Generar respuesta para notas de estudiante"""
        if not data:
            return "No se encontraron notas para este estudiante."
        
        student_name = f"{data[0].get('nombre', '')} {data[0].get('apellido', '')}"
        response = f"Notas de {student_name}:\n\n"
        
        for grade in data:
            materia = grade.get('materia', 'N/A')
            nota = grade.get('nota', 'N/A')
            cuatrimestre = grade.get('cuatrimestre', 'N/A')
            response += f"• {materia}: {nota} (Cuatrimestre {cuatrimestre})\n"
        
        return response
    
    def _generate_course_stats_response(self, data: List[Dict], analysis: Dict) -> str:
        """Generar respuesta para estadísticas de curso"""
        if not data:
            return "No hay datos disponibles para este curso."
        
        response = "Estadísticas del curso:\n\n"
        
        for stat in data:
            curso = f"{stat.get('anio', '')}° {stat.get('division', '')}"
            estudiantes = stat.get('total_estudiantes', 0)
            promedio = stat.get('promedio_notas', 0)
            
            response += f"• {curso}:\n"
            response += f"  - Estudiantes: {estudiantes}\n"
            response += f"  - Promedio de notas: {promedio:.2f}\n"
        
        return response
    
    def _generate_attention_calls_response(self, data: List[Dict]) -> str:
        """Generar respuesta para llamados de atención"""
        if not data:
            return "No hay llamados de atención registrados."
        
        response = f"Llamados de atención ({len(data)} registros):\n\n"
        
        for call in data[:5]:  # Mostrar máximo 5
            estudiante = f"{call.get('nombre', '')} {call.get('apellido', '')}"
            motivo = call.get('motivo', 'N/A')
            fecha = call.get('fecha', 'N/A')
            
            response += f"• {estudiante} - {fecha}\n"
            response += f"  Motivo: {motivo}\n\n"
        
        return response
    
    def _generate_teachers_response(self, data: List[Dict]) -> str:
        """Generar respuesta para profesores"""
        if not data:
            return "No se encontraron profesores."
        
        response = f"Profesores ({len(data)} registros):\n\n"
        
        for teacher in data[:10]:  # Mostrar máximo 10
            nombre = f"{teacher.get('nombre', '')} {teacher.get('apellido', '')}"
            email = teacher.get('email', 'N/A')
            response += f"• {nombre} - {email}\n"
        
        return response
    
    def _generate_general_stats_response(self, data: List[Dict]) -> str:
        """Generar respuesta para estadísticas generales"""
        if not data:
            return "No hay estadísticas disponibles."
        
        response = "Estadísticas del sistema:\n\n"
        
        for stat in data:
            tabla = stat.get('tabla', 'N/A')
            total = stat.get('total', 0)
            response += f"• {tabla.capitalize()}: {total}\n"
        
        return response
    
    def _generate_generic_response(self, data: List[Dict]) -> str:
        """Generar respuesta genérica"""
        count = len(data)
        return f"Se encontraron {count} registros que coinciden con tu consulta."
