// JavaScript para SistemaAdmin AI Assistant

class AIAssistant {
    constructor() {
        this.isLoading = false;
        this.chatMessages = document.getElementById('chat-messages');
        this.chatInput = document.getElementById('chat-input');
        this.chatForm = document.getElementById('chat-form');
        this.sendBtn = document.getElementById('send-btn');
        this.connectionStatus = document.getElementById('connection-status');
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.checkSystemHealth();
        this.loadSystemStats();
        
        // Auto-focus en el input
        this.chatInput.focus();
    }
    
    setupEventListeners() {
        // Envío de mensajes
        this.chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        // Enter para enviar (Shift+Enter para nueva línea)
        this.chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Acciones rápidas
        document.querySelectorAll('.quick-action').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const question = e.target.getAttribute('data-question');
                this.chatInput.value = question;
                this.sendMessage();
            });
        });
        
        // Preguntas de ejemplo
        document.querySelectorAll('.example-question').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const question = e.target.getAttribute('data-question');
                this.chatInput.value = question;
                this.sendMessage();
            });
        });
    }
    
    async sendMessage() {
        const message = this.chatInput.value.trim();
        if (!message || this.isLoading) return;
        
        // Agregar mensaje del usuario
        this.addMessage(message, 'user');
        this.chatInput.value = '';
        
        // Mostrar indicador de carga
        this.showLoading();
        
        try {
            const response = await this.processQuery(message);
            this.hideLoading();
            this.addMessage(response.response, 'ai', response.data, response.analysis);
        } catch (error) {
            this.hideLoading();
            this.addMessage('Lo siento, hubo un error procesando tu consulta. Intenta nuevamente.', 'ai');
            console.error('Error:', error);
        }
    }
    
    async processQuery(question) {
        const formData = new FormData();
        formData.append('question', question);
        
        const response = await fetch('/api/query', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
    
    addMessage(content, type, data = null, analysis = null) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}-message`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        if (type === 'ai') {
            contentDiv.innerHTML = `<i class="fas fa-robot me-2"></i>${this.formatResponse(content)}`;
        } else {
            contentDiv.innerHTML = `<i class="fas fa-user me-2"></i>${content}`;
        }
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = new Date().toLocaleTimeString();
        
        messageDiv.appendChild(contentDiv);
        messageDiv.appendChild(timeDiv);
        
        // Agregar datos si existen
        if (data && data.length > 0) {
            const dataDiv = this.createDataDisplay(data, analysis);
            messageDiv.appendChild(dataDiv);
        }
        
        this.chatMessages.appendChild(messageDiv);
        this.scrollToBottom();
    }
    
    formatResponse(content) {
        // Formatear respuesta con saltos de línea
        return content.replace(/\n/g, '<br>');
    }
    
    createDataDisplay(data, analysis) {
        const dataDiv = document.createElement('div');
        dataDiv.className = 'response-data mt-2';
        
        // Crear tabla de datos
        if (Array.isArray(data) && data.length > 0) {
            const table = this.createDataTable(data);
            dataDiv.appendChild(table);
        }
        
        // Agregar análisis si existe
        if (analysis && Object.keys(analysis).length > 0) {
            const analysisDiv = this.createAnalysisDisplay(analysis);
            dataDiv.appendChild(analysisDiv);
        }
        
        return dataDiv;
    }
    
    createDataTable(data) {
        const table = document.createElement('table');
        table.className = 'table table-sm data-table';
        
        // Crear encabezados
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        
        const headers = Object.keys(data[0]);
        headers.forEach(header => {
            const th = document.createElement('th');
            th.textContent = this.formatHeader(header);
            headerRow.appendChild(th);
        });
        
        thead.appendChild(headerRow);
        table.appendChild(thead);
        
        // Crear filas de datos
        const tbody = document.createElement('tbody');
        data.slice(0, 10).forEach(row => { // Mostrar máximo 10 filas
            const tr = document.createElement('tr');
            headers.forEach(header => {
                const td = document.createElement('td');
                td.textContent = row[header] || '-';
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        
        table.appendChild(tbody);
        
        // Agregar nota si hay más datos
        if (data.length > 10) {
            const note = document.createElement('div');
            note.className = 'text-muted small mt-2';
            note.textContent = `Mostrando 10 de ${data.length} registros`;
            table.parentNode.appendChild(note);
        }
        
        return table;
    }
    
    createAnalysisDisplay(analysis) {
        const analysisDiv = document.createElement('div');
        analysisDiv.className = 'mt-3';
        
        const title = document.createElement('h6');
        title.textContent = 'Análisis';
        title.className = 'text-primary mb-2';
        analysisDiv.appendChild(title);
        
        // Crear estadísticas
        if (analysis.total_records) {
            const statsDiv = document.createElement('div');
            statsDiv.className = 'response-stats';
            
            // Agregar estadísticas relevantes
            if (analysis.course_distribution) {
                this.addStatCard(statsDiv, 'Cursos', Object.keys(analysis.course_distribution).length);
            }
            
            if (analysis.specialty_distribution) {
                this.addStatCard(statsDiv, 'Especialidades', Object.keys(analysis.specialty_distribution).length);
            }
            
            if (analysis.average_grade) {
                this.addStatCard(statsDiv, 'Promedio', analysis.average_grade);
            }
            
            if (analysis.total_records) {
                this.addStatCard(statsDiv, 'Total', analysis.total_records);
            }
            
            analysisDiv.appendChild(statsDiv);
        }
        
        return analysisDiv;
    }
    
    addStatCard(container, label, value) {
        const statDiv = document.createElement('div');
        statDiv.className = 'stat-item';
        
        const valueDiv = document.createElement('div');
        valueDiv.className = 'stat-value';
        valueDiv.textContent = value;
        
        const labelDiv = document.createElement('div');
        labelDiv.className = 'stat-label';
        labelDiv.textContent = label;
        
        statDiv.appendChild(valueDiv);
        statDiv.appendChild(labelDiv);
        container.appendChild(statDiv);
    }
    
    formatHeader(header) {
        // Formatear nombres de columnas
        return header
            .replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }
    
    showLoading() {
        this.isLoading = true;
        this.sendBtn.disabled = true;
        this.sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Mostrar indicador de escritura
        this.showTypingIndicator();
    }
    
    hideLoading() {
        this.isLoading = false;
        this.sendBtn.disabled = false;
        this.sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        
        // Ocultar indicador de escritura
        this.hideTypingIndicator();
    }
    
    showTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message ai-message typing-indicator show';
        typingDiv.id = 'typing-indicator';
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.innerHTML = `
            <i class="fas fa-robot me-2"></i>
            <span class="typing-dots"></span>
            <span class="typing-dots"></span>
            <span class="typing-dots"></span>
        `;
        
        typingDiv.appendChild(contentDiv);
        this.chatMessages.appendChild(typingDiv);
        this.scrollToBottom();
    }
    
    hideTypingIndicator() {
        const typingDiv = document.getElementById('typing-indicator');
        if (typingDiv) {
            typingDiv.remove();
        }
    }
    
    scrollToBottom() {
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    }
    
    async checkSystemHealth() {
        try {
            const response = await fetch('/api/health');
            const data = await response.json();
            
            if (data.status === 'healthy') {
                this.connectionStatus.textContent = 'Conectado';
                this.connectionStatus.className = 'connection-online';
                this.updateSystemStats(data);
            } else {
                this.connectionStatus.textContent = 'Desconectado';
                this.connectionStatus.className = 'connection-offline';
            }
        } catch (error) {
            this.connectionStatus.textContent = 'Error de conexión';
            this.connectionStatus.className = 'connection-offline';
            console.error('Error checking system health:', error);
        }
    }
    
    async loadSystemStats() {
        try {
            const response = await fetch('/api/stats');
            const data = await response.json();
            
            if (data.success) {
                this.updateSystemStats(data.stats);
            }
        } catch (error) {
            console.error('Error loading system stats:', error);
        }
    }
    
    updateSystemStats(stats) {
        // Actualizar contadores
        if (stats.estudiantes !== undefined) {
            document.getElementById('students-count').textContent = stats.estudiantes;
        }
        
        if (stats.profesores !== undefined) {
            document.getElementById('teachers-count').textContent = stats.profesores;
        }
        
        // Actualizar estado de BD
        if (stats.database === 'connected') {
            document.getElementById('db-status').textContent = 'Conectado';
            document.getElementById('db-status').className = 'badge bg-success';
        } else {
            document.getElementById('db-status').textContent = 'Desconectado';
            document.getElementById('db-status').className = 'badge bg-danger';
        }
        
        // Actualizar timestamp
        document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
    }
}

// Inicializar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new AIAssistant();
    
    // Verificar salud del sistema cada 30 segundos
    setInterval(() => {
        const assistant = new AIAssistant();
        assistant.checkSystemHealth();
    }, 30000);
});

// Manejar errores globales
window.addEventListener('error', (e) => {
    console.error('Error global:', e.error);
});

// Manejar errores de fetch
window.addEventListener('unhandledrejection', (e) => {
    console.error('Error de promesa no manejada:', e.reason);
});
