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
      margin: [8, 8],
      filename: fileName,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { 
        scale: 2, 
        useCORS: true, 
        letterRendering: true,
        logging: false
      },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
      pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
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
    const initials = (cv.fullName || user?.fullName || 'ST').split(' ').map(p => p[0]).join('').toUpperCase();
    return `
      <div class="cv-preview" style="padding: 10mm; background: #fff; color: #111; font-family: sans-serif; width: 210mm; min-height: 295mm; box-sizing: border-box;">
        <div style="display: flex; gap: 12px; align-items: center; border-bottom: 2px solid #6366f1; padding-bottom: 10px; margin-bottom: 15px;">
          <div style="width: 50px; height: 50px; background: #6366f1; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: bold; border-radius: 6px;">${initials}</div>
          <div style="flex: 1;">
            <h1 style="margin: 0; font-size: 1.6rem; line-height: 1.1;">${cv.fullName || user?.fullName || 'Student'}</h1>
            <div style="font-size: 1rem; color: #4f46e5; font-weight: 600; margin-top: 2px;">${cv.profession || 'Professional Title'}</div>
            <div style="margin-top: 6px; font-size: 0.8rem; color: #666; display: flex; gap: 12px; flex-wrap: wrap;">
              <span>${cv.email || user?.email || ''}</span>
              <span>•</span>
              <span>${cv.phone || ''}</span>
              <span>•</span>
              <span>${cv.address || ''}</span>
            </div>
          </div>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 10px;">
          ${this.renderSection('Professional Summary', cv.summary)}
          ${this.renderSection('Education', cv.education)}
          ${this.renderSection('Work Experience', cv.experience)}
          ${this.renderSection('Projects', cv.projects)}
          ${this.renderSection('Technical Skills', cv.technicalSkills, true)}
          ${this.renderSection('Soft Skills', cv.softSkills, true)}
          ${this.renderSection('Languages', cv.languages)}
          ${this.renderSection('Certifications', cv.certifications)}
        </div>
      </div>
    `;
  },

  renderSection(title, content, isSkills = false) {
    if (!content || (Array.isArray(content) && content.length === 0)) return '';
    
    let bodyHtml = '';
    if (isSkills) {
      const items = Array.isArray(content) ? content : content.split(',').map(s => s.trim());
      bodyHtml = `<div style="display: flex; flex-wrap: wrap; gap: 5px;">` + 
        items.map(s => `<span style="background: #f1f5f9; color: #334151; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; border: 1px solid #e2e8f0;">${s}</span>`).join('') + 
        `</div>`;
    } else {
      const body = Array.isArray(content) ? content.join(', ') : content;
      bodyHtml = `<p style="font-size: 0.85rem; line-height: 1.4; color: #374151; white-space: pre-wrap; margin: 0;">${body}</p>`;
    }

    return `
      <div style="page-break-inside: avoid;">
        <h2 style="font-size: 0.9rem; color: #111; border-bottom: 1px solid #e5e7eb; padding-bottom: 2px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">${title}</h2>
        ${bodyHtml}
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
