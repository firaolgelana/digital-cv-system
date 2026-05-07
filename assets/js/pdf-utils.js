/**
 * PDF Utilities for DigiCV
 * Uses html2pdf.js for high-fidelity CV exports
 */
window.PDFUtils = {
  /**
   * Generates a PDF from a given HTML element
   * @param {HTMLElement} element 
   * @param {Object} options 
   */
  async generateCV(element, options = {}) {
    const defaultOptions = {
      fullName: 'Student',
      status: 'Approved'
    };
    const settings = { ...defaultOptions, ...options };
    const fileName = `${settings.fullName.replace(/\s+/g, '_')}_DigiCV.pdf`;

    const opt = {
      margin: [10, 10],
      filename: fileName,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { 
        scale: 2, 
        useCORS: true, 
        letterRendering: true,
        logging: false
      },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    try {
      await html2pdf().set(opt).from(element).save();
      return true;
    } catch (error) {
      console.error('PDF Generation Error:', error);
      throw error;
    }
  },

  /**
   * Helper to show/hide loading state on buttons
   */
  /**
   * Generates a PDF from CV data by rendering it in a hidden container
   * @param {Object} cv 
   * @param {Object} user 
   */
  async downloadCvPdf(cv, user) {
    const temp = document.createElement('div');
    temp.style.position = 'absolute';
    temp.style.left = '-9999px';
    temp.style.top = '-9999px';
    temp.innerHTML = this.buildCvHtml(cv, user);
    document.body.appendChild(temp);

    try {
      await this.generateCV(temp.querySelector('.cv-preview'), {
        fullName: cv.fullName || user?.fullName || 'Student'
      });
    } finally {
      document.body.removeChild(temp);
    }
  },

  buildCvHtml(cv, user) {
    // Shared template logic for high-fidelity PDF
    const initials = (cv.fullName || user?.fullName || 'ST').split(' ').map(p => p[0]).join('').toUpperCase();
    return `
      <div class="cv-preview" style="padding: 20px; background: #fff; color: #1a1a1a; font-family: sans-serif; width: 210mm; min-height: 297mm; box-sizing: border-box;">
        <div style="display: flex; gap: 20px; align-items: center; border-bottom: 2px solid #6366f1; padding-bottom: 20px; margin-bottom: 25px;">
          <div style="width: 80px; height: 80px; background: #6366f1; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; border-radius: 8px;">${initials}</div>
          <div>
            <h1 style="margin: 0; font-size: 2.2rem; color: #111;">${cv.fullName || user?.fullName || 'Student'}</h1>
            <div style="font-size: 1.1rem; color: #4f46e5; font-weight: 600;">${cv.profession || 'Professional Title'}</div>
            <div style="margin-top: 8px; font-size: 0.85rem; color: #666; display: flex; gap: 15px;">
              <span>${cv.email || user?.email || ''}</span>
              <span>${cv.phone || ''}</span>
              <span>${cv.address || ''}</span>
            </div>
          </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 25px;">
          ${this.renderSection('Professional Summary', cv.summary)}
          ${this.renderSection('Education', cv.education)}
          ${this.renderSection('Work Experience', cv.experience)}
          ${this.renderSection('Projects', cv.projects)}
          ${this.renderSection('Technical Skills', cv.technicalSkills)}
          ${this.renderSection('Soft Skills', cv.softSkills)}
          ${this.renderSection('Languages', cv.languages)}
          ${this.renderSection('Certifications', cv.certifications)}
        </div>
      </div>
    `;
  },

  renderSection(title, content) {
    if (!content || (Array.isArray(content) && content.length === 0)) return '';
    const body = Array.isArray(content) ? content.join(', ') : content;
    return `
      <div>
        <h2 style="font-size: 1.1rem; color: #111; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">${title}</h2>
        <p style="font-size: 0.95rem; line-height: 1.6; color: #374151; white-space: pre-wrap; margin: 0;">${body}</p>
      </div>
    `;
  },
  
  setLoading(btn, isLoading, originalText) {
    if (!btn) return;
    btn.disabled = isLoading;
    if (isLoading) {
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    } else {
      btn.innerHTML = originalText;
    }
  }
};
