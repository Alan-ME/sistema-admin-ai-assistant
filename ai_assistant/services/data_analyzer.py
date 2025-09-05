"""
Analizador de datos para SistemaAdmin AI
Procesa y analiza resultados de consultas SQL
"""

import logging
from typing import Dict, List, Any, Optional
from datetime import datetime, timedelta
import statistics

logger = logging.getLogger(__name__)

class DataAnalyzer:
    """Analizador de datos para generar insights y estadísticas"""
    
    def __init__(self, api_client):
        self.api = api_client
    
    def analyze_results(self, data: List[Dict], intent: str) -> Dict[str, Any]:
        """Analizar resultados de consulta y generar insights"""
        try:
            if not data:
                return {"message": "No hay datos para analizar"}
            
            analysis = {
                "total_records": len(data),
                "timestamp": datetime.now().isoformat(),
                "intent": intent
            }
            
            if intent == "listar_estudiantes":
                analysis.update(self._analyze_students(data))
            elif intent == "notas_estudiante":
                analysis.update(self._analyze_grades(data))
            elif intent == "estadisticas_curso":
                analysis.update(self._analyze_course_stats(data))
            elif intent == "llamados_atencion":
                analysis.update(self._analyze_attention_calls(data))
            elif intent == "profesores":
                analysis.update(self._analyze_teachers(data))
            elif intent == "estadisticas_generales":
                analysis.update(self._analyze_general_stats(data))
            else:
                analysis.update(self._analyze_generic(data))
            
            return analysis
            
        except Exception as e:
            logger.error(f"❌ Error analizando datos: {e}")
            return {"error": str(e)}
    
    def _analyze_students(self, data: List[Dict]) -> Dict[str, Any]:
        """Analizar datos de estudiantes"""
        analysis = {}
        
        # Distribución por curso
        course_distribution = {}
        for student in data:
            curso = f"{student.get('anio', 'N/A')}° {student.get('division', 'N/A')}"
            course_distribution[curso] = course_distribution.get(curso, 0) + 1
        
        analysis["course_distribution"] = course_distribution
        
        # Distribución por especialidad
        specialty_distribution = {}
        for student in data:
            especialidad = student.get('especialidad', 'Sin especialidad')
            specialty_distribution[especialidad] = specialty_distribution.get(especialidad, 0) + 1
        
        analysis["specialty_distribution"] = specialty_distribution
        
        # Distribución por turno
        shift_distribution = {}
        for student in data:
            turno = student.get('turno', 'Sin turno')
            shift_distribution[turno] = shift_distribution.get(turno, 0) + 1
        
        analysis["shift_distribution"] = shift_distribution
        
        return analysis
    
    def _analyze_grades(self, data: List[Dict]) -> Dict[str, Any]:
        """Analizar datos de notas"""
        analysis = {}
        
        if not data:
            return analysis
        
        # Extraer notas numéricas
        grades = [float(grade.get('nota', 0)) for grade in data if grade.get('nota')]
        
        if grades:
            analysis["average_grade"] = round(statistics.mean(grades), 2)
            analysis["max_grade"] = max(grades)
            analysis["min_grade"] = min(grades)
            analysis["total_grades"] = len(grades)
            
            # Distribución por rango de notas
            grade_ranges = {
                "Excelente (9-10)": len([g for g in grades if g >= 9]),
                "Bueno (7-8.9)": len([g for g in grades if 7 <= g < 9]),
                "Regular (6-6.9)": len([g for g in grades if 6 <= g < 7]),
                "Insuficiente (<6)": len([g for g in grades if g < 6])
            }
            analysis["grade_distribution"] = grade_ranges
        
        # Análisis por materia
        subject_analysis = {}
        for grade in data:
            materia = grade.get('materia', 'N/A')
            if materia not in subject_analysis:
                subject_analysis[materia] = []
            if grade.get('nota'):
                subject_analysis[materia].append(float(grade['nota']))
        
        # Calcular promedios por materia
        subject_averages = {}
        for materia, notas in subject_analysis.items():
            if notas:
                subject_averages[materia] = round(statistics.mean(notas), 2)
        
        analysis["subject_averages"] = subject_averages
        
        return analysis
    
    def _analyze_course_stats(self, data: List[Dict]) -> Dict[str, Any]:
        """Analizar estadísticas de curso"""
        analysis = {}
        
        if not data:
            return analysis
        
        # Total de estudiantes
        total_students = sum(course.get('total_estudiantes', 0) for course in data)
        analysis["total_students"] = total_students
        
        # Promedio general de notas
        grades = [course.get('promedio_notas', 0) for course in data if course.get('promedio_notas')]
        if grades:
            analysis["overall_average"] = round(statistics.mean(grades), 2)
        
        # Curso con más estudiantes
        max_students_course = max(data, key=lambda x: x.get('total_estudiantes', 0))
        analysis["largest_course"] = {
            "course": f"{max_students_course.get('anio', '')}° {max_students_course.get('division', '')}",
            "students": max_students_course.get('total_estudiantes', 0)
        }
        
        # Curso con mejor promedio
        best_avg_course = max(data, key=lambda x: x.get('promedio_notas', 0))
        analysis["best_performing_course"] = {
            "course": f"{best_avg_course.get('anio', '')}° {best_avg_course.get('division', '')}",
            "average": best_avg_course.get('promedio_notas', 0)
        }
        
        return analysis
    
    def _analyze_attention_calls(self, data: List[Dict]) -> Dict[str, Any]:
        """Analizar llamados de atención"""
        analysis = {}
        
        if not data:
            return analysis
        
        # Total de llamados
        analysis["total_calls"] = len(data)
        
        # Llamados por estudiante
        student_calls = {}
        for call in data:
            estudiante = f"{call.get('nombre', '')} {call.get('apellido', '')}"
            student_calls[estudiante] = student_calls.get(estudiante, 0) + 1
        
        analysis["calls_per_student"] = student_calls
        
        # Estudiante con más llamados
        if student_calls:
            max_calls_student = max(student_calls.items(), key=lambda x: x[1])
            analysis["student_with_most_calls"] = {
                "name": max_calls_student[0],
                "calls": max_calls_student[1]
            }
        
        # Análisis por motivo (simplificado)
        motives = [call.get('motivo', 'N/A') for call in data]
        motive_counts = {}
        for motive in motives:
            # Simplificar motivos para análisis
            if 'tarde' in motive.lower():
                motive_counts['Llegadas tarde'] = motive_counts.get('Llegadas tarde', 0) + 1
            elif 'celular' in motive.lower():
                motive_counts['Uso de celular'] = motive_counts.get('Uso de celular', 0) + 1
            elif 'falta' in motive.lower():
                motive_counts['Falta de material'] = motive_counts.get('Falta de material', 0) + 1
            else:
                motive_counts['Otros'] = motive_counts.get('Otros', 0) + 1
        
        analysis["motive_distribution"] = motive_counts
        
        return analysis
    
    def _analyze_teachers(self, data: List[Dict]) -> Dict[str, Any]:
        """Analizar datos de profesores"""
        analysis = {}
        
        if not data:
            return analysis
        
        # Total de profesores
        analysis["total_teachers"] = len(data)
        
        # Profesores activos vs inactivos
        active_teachers = len([t for t in data if t.get('activo', False)])
        analysis["active_teachers"] = active_teachers
        analysis["inactive_teachers"] = len(data) - active_teachers
        
        # Análisis por antigüedad (simplificado)
        current_year = datetime.now().year
        teachers_by_experience = {
            "Nuevos (0-2 años)": 0,
            "Intermedios (3-10 años)": 0,
            "Experimentados (10+ años)": 0
        }
        
        for teacher in data:
            fecha_ingreso = teacher.get('fecha_ingreso')
            if fecha_ingreso:
                try:
                    year = int(fecha_ingreso.split('-')[0])
                    experience = current_year - year
                    
                    if experience <= 2:
                        teachers_by_experience["Nuevos (0-2 años)"] += 1
                    elif experience <= 10:
                        teachers_by_experience["Intermedios (3-10 años)"] += 1
                    else:
                        teachers_by_experience["Experimentados (10+ años)"] += 1
                except:
                    pass
        
        analysis["experience_distribution"] = teachers_by_experience
        
        return analysis
    
    def _analyze_general_stats(self, data: List[Dict]) -> Dict[str, Any]:
        """Analizar estadísticas generales"""
        analysis = {}
        
        if not data:
            return analysis
        
        # Convertir a diccionario para fácil acceso
        stats_dict = {stat.get('tabla', ''): stat.get('total', 0) for stat in data}
        
        analysis["system_stats"] = stats_dict
        
        # Calcular totales
        total_records = sum(stats_dict.values())
        analysis["total_records"] = total_records
        
        # Identificar la tabla con más registros
        if stats_dict:
            max_table = max(stats_dict.items(), key=lambda x: x[1])
            analysis["largest_table"] = {
                "table": max_table[0],
                "records": max_table[1]
            }
        
        return analysis
    
    def _analyze_generic(self, data: List[Dict]) -> Dict[str, Any]:
        """Análisis genérico para datos no específicos"""
        analysis = {
            "record_count": len(data),
            "has_data": len(data) > 0
        }
        
        if data:
            # Análisis básico de campos
            sample_record = data[0]
            analysis["available_fields"] = list(sample_record.keys())
            analysis["sample_data"] = sample_record
        
        return analysis
    
    def get_system_statistics(self) -> Dict[str, Any]:
        """Obtener estadísticas generales del sistema"""
        try:
            # Obtener estadísticas desde la API
            result = self.api.get_estadisticas()
            
            if result.get('success', False):
                stats = result.get('data', {})
                
                # Calcular métricas adicionales
                total_students = stats.get('estudiantes', 0)
                total_grades = stats.get('notas', 0)
                total_calls = stats.get('llamados', 0)
                
                return {
                    "estudiantes": total_students,
                    "profesores": stats.get('profesores', 0),
                    "cursos": stats.get('cursos', 0),
                    "materias": stats.get('materias', 0),
                    "notas": total_grades,
                    "llamados": total_calls,
                    "promedio_notas_por_estudiante": round(total_grades / total_students, 2) if total_students > 0 else 0,
                    "llamados_por_estudiante": round(total_calls / total_students, 2) if total_students > 0 else 0,
                    "timestamp": datetime.now().isoformat()
                }
            
            return {"error": "No se pudieron obtener estadísticas"}
            
        except Exception as e:
            logger.error(f"❌ Error obteniendo estadísticas del sistema: {e}")
            return {"error": str(e)}
    
    def detect_anomalies(self, data: List[Dict], data_type: str) -> List[Dict[str, Any]]:
        """Detectar anomalías en los datos"""
        anomalies = []
        
        try:
            if data_type == "grades" and data:
                # Detectar notas muy bajas o muy altas
                grades = [float(grade.get('nota', 0)) for grade in data if grade.get('nota')]
                if grades:
                    avg_grade = statistics.mean(grades)
                    std_dev = statistics.stdev(grades) if len(grades) > 1 else 0
                    
                    for grade in data:
                        if grade.get('nota'):
                            nota = float(grade['nota'])
                            if nota < (avg_grade - 2 * std_dev) or nota > (avg_grade + 2 * std_dev):
                                anomalies.append({
                                    "type": "outlier_grade",
                                    "student": f"{grade.get('nombre', '')} {grade.get('apellido', '')}",
                                    "grade": nota,
                                    "expected_range": f"{avg_grade - std_dev:.1f} - {avg_grade + std_dev:.1f}"
                                })
            
            elif data_type == "attendance" and data:
                # Detectar estudiantes con muchas inasistencias
                for record in data:
                    inasistencias = record.get('total_inasistencias', 0)
                    if inasistencias > 10:  # Umbral configurable
                        anomalies.append({
                            "type": "high_absences",
                            "student": f"{record.get('nombre', '')} {record.get('apellido', '')}",
                            "absences": inasistencias,
                            "threshold": 10
                        })
        
        except Exception as e:
            logger.error(f"❌ Error detectando anomalías: {e}")
        
        return anomalies
