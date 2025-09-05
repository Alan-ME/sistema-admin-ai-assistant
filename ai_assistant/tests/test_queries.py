"""
Tests de consultas SQL para SistemaAdmin AI
"""

import pytest
from database.queries import SistemaAdminQueries

class TestSistemaAdminQueries:
    """Tests para las consultas SQL predefinidas"""
    
    def test_queries_class_creation(self):
        """Test crear instancia de consultas"""
        queries = SistemaAdminQueries()
        assert queries is not None
    
    def test_get_all_students_query(self):
        """Test consulta de todos los estudiantes"""
        queries = SistemaAdminQueries()
        query = queries.GET_ALL_STUDENTS
        
        assert "SELECT" in query
        assert "estudiantes" in query
        assert "e.id" in query
        assert "e.nombre" in query
        assert "e.apellido" in query
    
    def test_get_students_by_course_query(self):
        """Test consulta de estudiantes por curso"""
        queries = SistemaAdminQueries()
        query = queries.GET_STUDENTS_BY_COURSE
        
        assert "SELECT" in query
        assert "estudiantes" in query
        assert "cursos" in query
        assert "%s" in query  # Parámetro
    
    def test_get_student_grades_query(self):
        """Test consulta de notas de estudiante"""
        queries = SistemaAdminQueries()
        query = queries.GET_STUDENT_GRADES
        
        assert "SELECT" in query
        assert "notas" in query
        assert "materias" in query
        assert "%s" in query  # Parámetro
    
    def test_get_all_teachers_query(self):
        """Test consulta de todos los profesores"""
        queries = SistemaAdminQueries()
        query = queries.GET_ALL_TEACHERS
        
        assert "SELECT" in query
        assert "profesores" in query
        assert "p.nombre" in query
        assert "p.apellido" in query
    
    def test_get_all_courses_query(self):
        """Test consulta de todos los cursos"""
        queries = SistemaAdminQueries()
        query = queries.GET_ALL_COURSES
        
        assert "SELECT" in query
        assert "cursos" in query
        assert "especialidades" in query
        assert "turnos" in query
    
    def test_get_attention_calls_query(self):
        """Test consulta de llamados de atención"""
        queries = SistemaAdminQueries()
        query = queries.GET_ATTENTION_CALLS
        
        assert "SELECT" in query
        assert "llamados_atencion" in query
        assert "estudiantes" in query
    
    def test_get_statistics_query(self):
        """Test consulta de estadísticas"""
        queries = SistemaAdminQueries()
        query = queries.GET_STATISTICS
        
        assert "SELECT" in query
        assert "estudiantes" in query
        assert "profesores" in query
        assert "cursos" in query
        assert "UNION ALL" in query
    
    def test_search_students_query(self):
        """Test consulta de búsqueda de estudiantes"""
        queries = SistemaAdminQueries()
        query = queries.SEARCH_STUDENTS
        
        assert "SELECT" in query
        assert "estudiantes" in query
        assert "LIKE" in query
        assert "%s" in query  # Parámetros
    
    def test_get_grade_distribution_query(self):
        """Test consulta de distribución de notas"""
        queries = SistemaAdminQueries()
        query = queries.GET_GRADE_DISTRIBUTION
        
        assert "SELECT" in query
        assert "notas" in query
        assert "CASE" in query
        assert "WHEN" in query
