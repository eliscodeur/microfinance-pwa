import React from 'react';

export default function BootstrapModal({
  show,
  title,
  body,
  onConfirm,
  onClose,
  confirmText = 'Oui',
  cancelText = 'Annuler',
  confirmVariant = 'primary',
  hideCancel = false,
}) {
  if (!show) {
    return null;
  }

  return (
    <div className="modal fade show d-block" tabIndex="-1" role="dialog" aria-modal="true" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
      <div className="modal-dialog modal-dialog-centered" role="document">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title">{title}</h5>
            <button type="button" className="btn-close" aria-label="Fermer" onClick={onClose} />
          </div>
          <div className="modal-body">
            <p>{body}</p>
          </div>
          <div className="modal-footer">
            {!hideCancel && (
              <button type="button" className="btn btn-secondary" onClick={onClose}>
                {cancelText}
              </button>
            )}
            <button type="button" className={`btn btn-${confirmVariant}`} onClick={onConfirm}>
              {confirmText}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
