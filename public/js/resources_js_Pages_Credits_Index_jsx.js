"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["resources_js_Pages_Credits_Index_jsx"],{

/***/ "./resources/js/Layouts/AdminLayout.jsx":
/*!**********************************************!*\
  !*** ./resources/js/Layouts/AdminLayout.jsx ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ AdminLayout)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");

function AdminLayout(_ref) {
  var children = _ref.children;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
    children: children
  });
}

/***/ }),

/***/ "./resources/js/Pages/Credits/Index.jsx":
/*!**********************************************!*\
  !*** ./resources/js/Pages/Credits/Index.jsx ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Index)
/* harmony export */ });
/* harmony import */ var _inertiajs_inertia__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @inertiajs/inertia */ "./node_modules/@inertiajs/inertia/dist/index.js");
/* harmony import */ var _inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/inertia-react */ "./node_modules/@inertiajs/inertia-react/dist/index.js");
/* harmony import */ var _Layouts_AdminLayout_jsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../Layouts/AdminLayout.jsx */ "./resources/js/Layouts/AdminLayout.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");




// Regroupement des configurations de statuts pour une maintenance plus propre

var STATUS_CONFIG = {
  pending: {
    label: 'En attente',
    "class": 'badge bg-warning text-dark'
  },
  approved: {
    label: 'Approuvé',
    "class": 'badge bg-success'
  },
  active: {
    label: 'Actif',
    "class": 'badge bg-primary'
  },
  in_arrears: {
    label: 'En retard',
    "class": 'badge bg-danger'
  },
  solder: {
    label: 'Soldé',
    "class": 'badge bg-success'
  },
  closed: {
    label: 'Clôturé',
    "class": 'badge bg-secondary'
  },
  rejected: {
    label: 'Rejeté',
    "class": 'badge bg-dark'
  }
};
var TYPE_LABELS = {
  compte: 'Sur compte',
  quinzaine: 'Quinzaine',
  mensuel: 'Mensuel'
};
var PERIODICITE_LABELS = {
  quinzaine: 'Quinzaine',
  mensuelle: 'Mensuelle'
};
function Index(_ref) {
  var credits = _ref.credits;
  // Formatage monétaire strict (XAF)
  var formatCurrency = function formatCurrency(value) {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XAF',
      maximumFractionDigits: 0
    }).format(value);
  };

  // Formatage des dates du format ISO/DB vers le format FR
  var formatDate = function formatDate(dateString) {
    if (!dateString) return '-';
    var date = new Date(dateString);
    return isNaN(date.getTime()) ? dateString : date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  };
  var getStatusBadge = function getStatusBadge(status) {
    var config = STATUS_CONFIG[status] || {
      label: 'Inconnu',
      "class": 'badge bg-info'
    };
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
      className: config["class"],
      children: config.label
    });
  };

  // Actions de pagination via le nouveau router Inertia
  var handlePagination = function handlePagination(url) {
    if (url) {
      router.visit(url, {
        preserveState: true,
        preserveScroll: true
      });
    }
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_Layouts_AdminLayout_jsx__WEBPACK_IMPORTED_MODULE_2__["default"], {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
        className: "d-flex justify-content-between align-items-center mb-4",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("h1", {
            className: "h3 mb-1 fw-bold text-dark",
            children: "Gestion des cr\xE9dits"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("p", {
            className: "text-muted mb-0",
            children: "Liste des demandes et suivi des encours cr\xE9dits clients."
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_1__.Link, {
          href: "/admin/credits/create",
          className: "btn btn-primary shadow-sm d-flex align-items-center gap-2",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("i", {
            className: "bi bi-plus-lg"
          }), " Nouvelle demande"]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
        className: "card shadow-sm border-0",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          className: "card-body p-0",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
            className: "table-responsive",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("table", {
              className: "table table-hover align-middle mb-0",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("thead", {
                className: "table-light text-uppercase fs-7 text-muted",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "ps-4",
                    style: {
                      width: '80px'
                    },
                    children: "#"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    children: "Client"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    children: "Montant"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    children: "Type"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    children: "P\xE9riodicit\xE9"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    children: "Statut"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    children: "\xC9ch\xE9ances"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    children: "Cr\xE9\xE9 le"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "pe-4 text-end",
                    style: {
                      width: '100px'
                    },
                    children: "Actions"
                  })]
                })
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tbody", {
                children: [credits.data.map(function (credit) {
                  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("td", {
                      className: "ps-4 fw-medium text-secondary",
                      children: ["#", credit.id]
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "fw-semibold text-dark",
                      children: credit.client ? "".concat(credit.client.nom, " ").concat(credit.client.prenom) : 'Client inconnu'
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "fw-bold text-dark",
                      children: formatCurrency(credit.montant_demande)
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
                        className: "text-capitalize",
                        children: TYPE_LABELS[credit.type] || credit.type
                      })
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
                        className: "text-capitalize",
                        children: PERIODICITE_LABELS[credit.periodicite] || credit.periodicite
                      })
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      children: getStatusBadge(credit.statut)
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("span", {
                        className: "badge bg-light text-dark border",
                        children: [credit.nombre_echeances, " \xE9ch\xE9ances"]
                      })
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-muted",
                      children: formatDate(credit.created_at)
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "pe-4 text-end",
                      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_1__.Link, {
                        href: "/admin/credits/".concat(credit.id),
                        className: "btn btn-sm btn-outline-primary px-3 rounded-pill",
                        children: "Voir"
                      })
                    })]
                  }, credit.id);
                }), credits.data.length === 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("tr", {
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                    colSpan: "9",
                    className: "text-center py-5 text-muted",
                    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                      className: "py-3",
                      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("i", {
                        className: "bi bi-inbox fs-2 d-block mb-2 text-secondary"
                      }), "Aucun dossier de cr\xE9dit trouv\xE9 dans le syst\xE8me."]
                    })
                  })
                })]
              })]
            })
          })
        })
      }), credits.links && credits.links.length > 3 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
        className: "mt-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
          className: "text-muted small",
          children: ["Affichage de ", credits.from || 0, " \xE0 ", credits.to || 0, " sur ", credits.total, " demandes"]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("nav", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("ul", {
            className: "pagination mb-0 pagination-sm",
            children: credits.links.map(function (link, index) {
              return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
                className: "page-item ".concat(link.active ? 'active' : '', " ").concat(!link.url ? 'disabled' : ''),
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
                  type: "button",
                  className: "page-link",
                  dangerouslySetInnerHTML: {
                    __html: link.label
                  },
                  onClick: function onClick() {
                    return handlePagination(link.url);
                  },
                  disabled: !link.url
                })
              }, index);
            })
          })
        })]
      })]
    })
  });
}

/***/ })

}]);