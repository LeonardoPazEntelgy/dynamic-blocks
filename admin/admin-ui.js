const { useState, useEffect, createElement: el, Fragment } = wp.element;
const { TextControl, Button, SelectControl } = wp.components;
const { render } = wp.element;

const generateIndexJs = (shortName, title, category, icon) => {
  return `(function(blocks, blockEditor, element) {
  var el = element.createElement;
  var RichText = blockEditor.RichText;

  blocks.registerBlockType('dbb/${shortName}', {
    title: '${title}',  
    icon: (() => {
        const raw = ${icon};
        if (raw.startsWith('<svg')) {
            return {
                src: () => el('span', { dangerouslySetInnerHTML: { __html: raw } })
            };
        }
        return raw;
    })(),
    category: '${category}',
    attributes: {
      content: {
        type: 'string',
        source: 'html',
        selector: 'p'
      }
    },
    edit: function(props) {
      return el(RichText, {
        tagName: 'p',
        value: props.attributes.content,
        onChange: function(content) {
          props.setAttributes({ content: content });
        },
        placeholder: 'Escribe algo...'
      });
    },
    save: function(props) {
      return el(RichText.Content, {
        tagName: 'p',
        value: props.attributes.content
      });
    }
  });
})(
  window.wp.blocks,
  window.wp.blockEditor,
  window.wp.element
);`;
};

const categories = [
  { label: 'Widgets', value: 'widgets' },
  { label: 'Diseño', value: 'layout' },
  { label: 'Texto', value: 'text' },
  { label: 'Media', value: 'media' },
];

function IconSelector({ value, onChange }) {
  const [tab, setTab] = useState('dashicons');
  const dashicons = [...(new Set([ // reduce repeticiones
    "admin-appearance", "admin-collapse", "admin-comments", "admin-generic", "admin-home", "admin-links", "admin-media", "admin-network", "admin-page", "admin-plugins",
    "admin-post", "admin-settings", "admin-site", "admin-tools", "admin-users", "align-center", "align-full-width", "align-left", "align-none", "align-right",
    "analytics", "archive", "arrow-down", "arrow-down-alt", "arrow-down-alt2", "arrow-left", "arrow-left-alt", "arrow-left-alt2", "arrow-right", "arrow-right-alt",
    "arrow-right-alt2", "arrow-up", "arrow-up-alt", "arrow-up-alt2", "art", "awards", "backup", "book", "book-alt", "buddicons-activity", "buddicons-bbpress-logo",
    "buddicons-buddypress-logo", "buddicons-community", "buddicons-forums", "buddicons-friends", "buddicons-groups", "buddicons-pm", "buddicons-replies", "buddicons-topics",
    "buddicons-tracking", "building", "businessman", "businesswoman", "calendar", "camera", "carrot", "cart", "chart-area", "chart-bar", "chart-line", "chart-pie",
    "clipboard", "cloud", "cloud-saved", "cloud-upload", "controls-back", "controls-forward", "controls-pause", "controls-play", "controls-repeat", "controls-skipback",
    "controls-skipforward", "controls-volumeoff", "controls-volumeon", "dashboard", "database", "dismiss", "download", "editor-aligncenter", "editor-alignleft",
    "editor-alignright", "editor-bold", "editor-break", "editor-code", "editor-contract", "editor-customchar", "editor-expand", "editor-help", "editor-indent",
    "editor-insertmore", "editor-italic", "editor-kitchensink", "editor-ol", "editor-outdent", "editor-paragraph", "editor-paste-text", "editor-paste-word",
    "editor-quote", "editor-removeformatting", "editor-rtl", "editor-spellcheck", "editor-strikethrough", "editor-textcolor", "editor-ul", "editor-underline",
    "editor-unlink", "editor-video", "email", "email-alt", "exerpt-view", "external", "facebook", "facebook-alt", "feedback", "filter", "flag", "format-aside",
    "format-audio", "format-chat", "format-gallery", "format-image", "format-links", "format-quote", "format-standard", "format-status", "format-video", "forms",
    "googleplus", "grid-view", "groups", "hammer", "heading", "heart", "id", "id-alt", "image-crop", "image-filter", "image-flip-horizontal",
    "image-flip-vertical", "image-rotate-left", "image-rotate-right", "images-alt", "images-alt2", "index-card", "info", "info-outline", "insert", "insert-after",
    "insert-before", "instagram", "layout", "leftright", "lightbulb", "list-view", "location", "location-alt", "lock", "marker", "media-archive", "media-audio",
    "media-code", "media-default", "media-document", "media-interactive", "media-spreadsheet", "media-text", "media-video", "megaphone", "menu", "menu-alt",
    "menu-alt2", "menu-alt3", "microphone", "migrate", "minus", "money", "move", "nametag", "networking", "no", "palmtree", "performance", "pets",
    "phone", "playlist-audio", "playlist-video", "plus", "plus-alt", "portfolio", "post-status", "post-trash", "pressthis", "products", "randomize",
    "redo", "remove", "rest-api", "rss", "saved", "screenoptions", "search", "share", "share-alt", "share-alt2", "shield", "shield-alt", "shortcode", "slides",
    "smartphone", "smiley", "sort", "sos", "star-empty", "star-filled", "star-half", "sticky", "store", "superhero", "superhero-alt", "tablet", "tag", "tagcloud",
    "testimonial", "text", "tickets", "translation", "trash", "twitter", "twitter-alt", "undo", "universal-access", "unlock", "update", "update-alt", "upload",
    "vault", "video-alt", "video-alt2", "video-alt3", "visibility", "warning", "welcome-add-page", "welcome-comments", "welcome-learn-more", "welcome-view-site",
    "welcome-widgets-menus", "welcome-write-blog", "whatsapp", "wordpress", "yes"
  ]))]; // recortado para rendimiento

  return el('div', { className: 'mb-4' },
    el('h5', null, 'Icono del Bloque'),

    el('div', { className: 'mb-3' },
      el('strong', null, 'Seleccionado: '),
      typeof value === 'string' && value.startsWith('<svg')
        ? el('span', {
            dangerouslySetInnerHTML: { __html: value },
            style: { width: '20px', height: '20px', display: 'inline-block', verticalAlign: 'middle' }
          })
        : typeof value === 'string' && value.startsWith('data:image/svg+xml')
        ? el('img', {
            src: value,
            style: { width: '20px', height: '20px', verticalAlign: 'middle' }
          })
        : el('span', {
            className: `dashicons dashicons-${value}`,
            style: { fontSize: '20px', verticalAlign: 'middle' }
          }),
      //el('span', { className: 'text-muted ms-2' }, value)
    ),

    el('div', { className: 'btn-group mb-3' },
      ['dashicons', 'upload', 'url'].map((t) =>
        el('button', {
          type: 'button',
          className: `btn btn-outline-secondary ${tab === t ? 'active' : ''}`,
          onClick: () => setTab(t),
        }, t.toUpperCase())
      )
    ),

    tab === 'dashicons' && el('div', {
      className: 'd-flex flex-wrap gap-2 border p-3 rounded',
      style: { maxHeight: '200px', overflowY: 'auto' }
    },
      dashicons.map((icon) =>
        el('div', {
          key: icon,
          className: 'border rounded p-2 text-center',
          style: { cursor: 'pointer', width: '40px' },
          onClick: () => onChange(icon)
        },
          el('span', { className: `dashicons dashicons-${icon}` })
        )
      )
    ),

    tab === 'upload' && el('div', {},
      el('input', {
        type: 'file',
        accept: '.svg',
        className: 'form-control',
        onChange: (e) => {
          const file = e.target.files[0];
          if (!file || file.type !== 'image/svg+xml') {
            alert('Solo se permiten archivos SVG.');
            return;
          }
          const reader = new FileReader();
          reader.onload = (event) => {
            onChange(event.target.result);
          };
          reader.readAsDataURL(file);
        }
      }),
      typeof value === 'string' && value.startsWith('data:image/svg+xml') && el('div', { className: 'mt-2' },
        el('strong', null, 'Vista previa:'),
        el('img', {
          src: value,
          style: { width: '40px', height: 'auto', display: 'block', marginTop: '5px' }
        })
      )
    ),

    tab === 'url' && el(TextControl, {
      label: 'URL del ícono SVG',
      value: typeof value === 'string' && value.startsWith('http') ? value : '',
      onChange: onChange
    })
  );
}


function BlockCreator() {
  const [blockName, setBlockName] = useState('');
  const [blockTitle, setBlockTitle] = useState('');
  const [isChildBlock, setIsChildBlock] = useState(false);
  const [category, setCategory] = useState('widgets');
  const [icon, setIcon] = useState('smiley');
  const [status, setStatus] = useState('');
  const [blocks, setBlocks] = useState([]);
  const [selectedAcfGroup, setSelectedAcfGroup] = useState('');
  const [editAcfGroup, setEditAcfGroup] = useState('');
  const [template, setTemplate] = useState('default');
  const templates = [
    { label: 'Bloque normal (contenido editable o ACF)', value: 'default' },
    { label: 'Contenedor (InnerBlocks)', value: 'innerblocks' }
  ];
  const [editingBlock, setEditingBlock] = useState(null);
  const [editTitle, setEditTitle] = useState('');
  const [editCategory, setEditCategory] = useState('widgets');
  const [editIcon, setEditIcon] = useState('');

  useEffect(() => {
    fetchBlocks();
  }, []);

  const fetchBlocks = () => {
    wp.apiFetch({ path: '/dynamic-blocks/v1/list' }).then(setBlocks);
  };

  const handleCreateBlock = () => {
    if (!blockName || !blockTitle) {
      showBootstrapAlert('⚠️ Por favor, completa todos los campos.');
      return;
    }

    wp.apiFetch({
      path: '/dynamic-blocks/v1/create',
      method: 'POST',
      data: { blockName, blockTitle, category, icon, acfGroup: selectedAcfGroup, template, isChildBlock }
    }).then((res) => {
      showBootstrapAlert(`✅ Bloque "${res.name}" creado.`);
      setBlockName('');
      setBlockTitle('');
      fetchBlocks();
    }).catch((err) => {
      showBootstrapAlert(`❌ Error: ${err.message}`);
    });
  };

  const handleDeleteBlock = (name) => {
    const shortName = name.replace(/^acf\//, '').replace(/^dbb[-/]/, '');
    if (!confirm(`¿Eliminar el bloque "${shortName}"?`)) return;

    wp.apiFetch({
      path: `/dynamic-blocks/v1/delete/${shortName}`,
      method: 'DELETE',
    }).then(() => {
      showBootstrapAlert(`🗑️ Bloque "${shortName}" eliminado.`);
      fetchBlocks();
    }).catch((err) => {
      showBootstrapAlert(`❌ Error al eliminar: ${err.message}`);
    });
  };

  const startEditingBlock = (name) => {
    const shortName = name.replace(/^acf\//, '').replace(/^dbb[-/]/, '');
    wp.apiFetch({ path: `/dynamic-blocks/v1/get/${shortName}` }).then((res) => {
      setEditingBlock(shortName);
      setEditTitle(res.blockJson.title);
      setEditCategory(res.blockJson.category);
      setEditIcon(res.blockJson.icon);
      setEditAcfGroup(res.blockJson.acfGroup || '');
    }).catch((err) => {
      showBootstrapAlert(`❌ Error al cargar bloque: ${err.message}`);
    });
  };

  const saveEditingBlock = () => {
    const shortName = editingBlock.replace(/^acf\//, '').replace(/^dbb[-/]/, '');
    const indexJs = generateIndexJs(shortName, editTitle, editCategory, editIcon);

    wp.apiFetch({
      path: `/dynamic-blocks/v1/update/${shortName}`,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        title: editTitle,
        category: editCategory,
        icon: editIcon,
        indexJs: indexJs,
        acfGroup: editAcfGroup
      })
    }).then(() => {
      showBootstrapAlert(`✅ Bloque "${editingBlock}" actualizado.`);
      setEditingBlock(null);
      fetchBlocks();
    }).catch((err) => {
      showBootstrapAlert(`❌ Error al guardar: ${err.message}`);
      console.error(err);
    });
  };
  const [activeTab, setActiveTab] = useState('crear');
  // Función para descargar el ZIP de bloques dinámicos
  const handleExportZip = () => {
    fetch('/wp-json/dynamic-blocks/v1/export-zip', {
      method: 'GET',
      credentials: 'include', // ✅ para que WordPress detecte la sesión
    })
      .then((res) => {
        if (!res.ok) throw new Error('Error al generar ZIP');
        return res.blob();
      })
      .then((blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'bloques-dinamicos.zip';
        a.click();
        window.URL.revokeObjectURL(url);
      })
      .catch((err) => {
        alert('❌ No se pudo descargar el ZIP: ' + err.message);
      });
  };

  return el(Fragment, null,
    el('div', { className: 'container-fluid' },
      // Nav Tabs
      el('ul', { className: 'nav nav-tabs mb-4' },
        el('li', { className: 'nav-item' },
          el('button', {
            className: `nav-link ${activeTab === 'crear' ? 'active' : ''}`,
            onClick: () => setActiveTab('crear')
          }, '🧱 Crear Bloque')
        ),
        el('li', { className: 'nav-item' },
          el('button', {
            className: `nav-link ${activeTab === 'listar' ? 'active' : ''}`,
            onClick: () => setActiveTab('listar')
          }, '📋 Listar Bloques')
        ),
        el('li', { className: 'nav-item' },
          el('button', {
            className: `nav-link ${activeTab === 'config' ? 'active' : ''}`,
            onClick: () => setActiveTab('config')
          }, '⚙️ Configuración')
        )
      ),

      // Sección Crear
      activeTab === 'crear' && el('div', { className: 'card shadow-sm mb-4' },
        el('div', { className: 'card-body p-0' },
          el('h2', { className: 'card-title mb-3' }, 'Crear Bloque Dinámico'),
          el('div', { className: 'mb-3' },
            el('label', { className: 'form-label' }, 'Nombre técnico del bloque'),
            el('input', {
              type: 'text',
              className: 'form-control',
              value: blockName,
              onChange: (e) => setBlockName(e.target.value)
            })
          ),
          el('div', { className: 'mb-3' },
            el('label', { className: 'form-label' }, 'Título visible del bloque'),
            el('input', {
              type: 'text',
              className: 'form-control',
              value: blockTitle,
              onChange: (e) => setBlockTitle(e.target.value)
            })
          ),
          el('div', { className: 'mb-3' },
            el('label', { className: 'form-label' }, 'Categoría'),
            el('select', {
              className: 'form-select',
              value: category,
              onChange: (e) => setCategory(e.target.value)
            },
              categories.map((cat) =>
                el('option', { key: cat.value, value: cat.value }, cat.label)
              )
            )
          ),
          el('div', { className: 'mb-3' },
            el('label', { className: 'form-label' }, 'Plantilla base'),
            el('select', {
              className: 'form-select',
              value: template,
              onChange: (e) => setTemplate(e.target.value)
            },
              templates.map((tmp) =>
                el('option', { key: tmp.value, value: tmp.value }, tmp.label)
              )
            )
          ),
          el('div', { className: 'form-check form-switch d-flex' },
            el('input', {
              className: 'form-check-input',
              type: 'checkbox',
              id: 'isChildSwitch',
              checked: isChildBlock,
              onChange: () => setIsChildBlock(!isChildBlock)
            }),
            el('label', {
              className: 'form-check-label',
              htmlFor: 'isChildSwitch'
            }, 'Marcar como bloque hijo (reutilizable dentro de contenedores)')
          ),
          el(IconSelector, {
            value: icon,
            onChange: setIcon,
          }),
          el(Button, {
            className: 'btn btn-primary',
            onClick: handleCreateBlock,
          }, 'Crear bloque'),
          status && el('p', { className: 'mt-2' }, status)
        )
      ),

      // Sección Listar
      activeTab === 'listar' && el('div', { className: 'card shadow-sm mb-4' },
        el('div', { className: 'card-body' },
          el('h2', { className: 'card-title' }, 'Bloques Existentes'),
          blocks.length === 0
            ? el('p', null, 'No hay bloques aún.')
            : el('ul', { className: 'list-group' },
                blocks.map((block) =>
                  el('li', {
                    key: block.name,
                    className: 'list-group-item d-flex justify-content-between align-items-center'
                  },
                    el('div', null,
                      el('strong', null, block.title),
                      ` (${block.name}) – ${block.category} ` //– ${block.icon}`
                    ),
                    el('div', {className: 'd-flex'}, 
                      el('button', {
                        className: 'btn btn-sm btn-outline-primary me-2',
                        onClick: () => startEditingBlock(block.name),
                        title: 'Editar',
                      }, el('span', { className: 'dashicons dashicons-edit' })),

                      el('button', {
                        className: 'btn btn-sm btn-outline-danger',
                        onClick: () => handleDeleteBlock(block.name),
                        title: 'Eliminar',
                      }, el('span', { className: 'dashicons dashicons-trash' }))
                    )
                  )
                )
              )
        )
      ),

      // Sección Configuración (por ahora vacía)
      activeTab === 'config' && el('div', { className: 'card shadow-sm mb-4' },
        el('div', { className: 'card-body' },
          el('h2', { className: 'card-title mb-4' }, '⚙️ Configuración del Plugin'),

          // Exportar
          el('div', { className: 'mb-4' },
            el('h5', null, '📤 Exportar bloques'),
            el('p', null, 'Exporta todos los bloques registrados a un archivo .Zip.'),
            el(Button, {
              className: 'btn btn-primary',
              variant: 'secondary',
              onClick: handleExportZip,
              style: { marginBottom: '10px' }
            }, 'Descargar ZIP')
          ),

          // Importar
          el('div', { className: 'mb-4' },
            el('h5', null, '📥 Importar bloques'),
            el('p', null, 'Importa bloques desde un archivo .json previamente exportado.'),
            el('div', { className: 'input-group' },
              el('input', {
                  className: 'form-control',
                  type: 'file',
                  accept: '.zip',
                  onChange: (e) => {
                    const file = e.target.files[0];
                    if (!file) return;
  
                    const formData = new FormData();
                    formData.append('file', file);
  
                    fetch('/wp-json/dynamic-blocks/v1/import-zip', {
                      method: 'POST',
                      credentials: 'include', // necesario para autenticar sesión
                      body: formData,
                    })
                      .then((res) => res.json())
                      .then((data) => {
                        if (data.success) {
                          alert('✅ Bloques importados: ' + data.imported.join(', '));
                          fetchBlocks();
                        } else {
                          alert('❌ Error: ' + data.message);
                        }
                      })
                      .catch((err) => {
                        alert('❌ No se pudo importar el ZIP.');
                        console.error(err);
                      });
                  }
                }
              ),
              el('label',{
                className: 'input-group-text',
              }, 'Upload')
            )
          ),

          // Feedback
          status && el('div', { className: 'alert alert-info' }, status)
        )
      ),
    ),
    // Modal edición
    editingBlock && el('div', {
        className: 'modal d-block',
        style: { backgroundColor: 'rgba(0,0,0,0.6)', zIndex: 1050 }
      },
      el('div', {
        className: 'modal-dialog modal-lg modal-dialog-centered'
      },
        el('div', { className: 'modal-content' },
          el('div', { className: 'modal-header' },
            el('h5', { className: 'modal-title' }, `Editar bloque: ${editingBlock}`),
            el('button', {
              type: 'button',
              className: 'btn-close',
              onClick: () => setEditingBlock(null)
            })
          ),
          el('div', { className: 'modal-body' },
            el(TextControl, {
              label: 'Título del bloque',
              value: editTitle,
              onChange: setEditTitle,
            }),
            el(SelectControl, {
              label: 'Categoría',
              value: editCategory,
              options: categories,
              onChange: setEditCategory,
            }),
            el(IconSelector, {
              value: editIcon,
              onChange: setEditIcon,
            })
          ),
          el('div', { className: 'modal-footer' },
            el(Button, {
              className: 'btn btn-primary',
              onClick: saveEditingBlock,
            }, 'Guardar'),
            el(Button, {
              className: 'btn btn-secondary',
              onClick: () => setEditingBlock(null),
            }, 'Cancelar')
          )
        )
      )
    )
  );  

  function showBootstrapAlert(message, type = 'success') {
    const container = document.createElement('div');
    container.className = `alert alert-${type} alert-dismissible fade show`;
    container.role = 'alert';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '1050';
    container.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(container);

    // Cerrar automáticamente en 3 segundos
    setTimeout(() => {
      container.classList.remove('show');
      container.classList.add('hide');
      setTimeout(() => {
        container.remove();
        location.reload(); // Recarga la página al cerrar
      }, 500);
    }, 1000);
  }  
}

document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('dbb-admin-root');
  if (root) {
    render(el(BlockCreator), root);
  }
});
