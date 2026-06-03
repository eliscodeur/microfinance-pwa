"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["resources_js_Pages_Prets_Index_jsx"],{

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

/***/ "./resources/js/Pages/Prets/Index.jsx":
/*!********************************************!*\
  !*** ./resources/js/Pages/Prets/Index.jsx ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Index)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/inertia-react */ "./node_modules/@inertiajs/inertia-react/dist/index.js");
/* harmony import */ var _Layouts_AdminLayout_jsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../Layouts/AdminLayout.jsx */ "./resources/js/Layouts/AdminLayout.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }




// Badges de statut professionnels et soft

var renderStatusBadge = function renderStatusBadge(statut) {
  switch (statut) {
    case 'pending':
    case 'soumis':
    case 'en_etude':
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "badge bg-secondary-subtle text-secondary border px-2.5 py-1.5 fw-medium",
        children: "En \xE9tude"
      });
    case 'approved':
    case 'approuve':
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "badge bg-success-subtle text-success border px-2.5 py-1.5 fw-medium",
        children: "Approuv\xE9"
      });
    case 'active':
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "badge bg-primary-subtle text-primary border px-2.5 py-1.5 fw-medium",
        children: "Actif"
      });
    case 'in_arrears':
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "badge bg-danger-subtle text-danger border px-2.5 py-1.5 fw-medium",
        children: "En retard"
      });
    case 'solder':
    case 'solde':
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "badge bg-info-subtle text-info border px-2.5 py-1.5 fw-medium",
        children: "Sold\xE9"
      });
    case 'rejected':
    case 'rejete':
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "badge bg-light text-muted border px-2.5 py-1.5 fw-medium",
        children: "Rejet\xE9"
      });
    default:
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "badge bg-light text-dark border px-2.5 py-1.5 fw-medium",
        children: statut
      });
  }
};
var formatCurrency = function formatCurrency(amount) {
  if (amount === null || amount === undefined || amount === '') {
    return '—';
  }
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    maximumFractionDigits: 0
  }).format(Number(amount));
};
var formatDateToFR = function formatDateToFR(value) {
  if (!value) return '—';
  var parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};
function Index(props) {
  var _props$creditsNonAppr = props.creditsNonApprouves,
    creditsNonApprouves = _props$creditsNonAppr === void 0 ? [] : _props$creditsNonAppr,
    _props$creditsApprouv = props.creditsApprouves,
    creditsApprouves = _props$creditsApprouv === void 0 ? [] : _props$creditsApprouv,
    _props$historique = props.historique,
    historique = _props$historique === void 0 ? [] : _props$historique;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('pending'),
    _useState2 = _slicedToArray(_useState, 2),
    tab = _useState2[0],
    setTab = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(''),
    _useState4 = _slicedToArray(_useState3, 2),
    searchQuery = _useState4[0],
    setSearchQuery = _useState4[1];

  // Filtrage local simple et propre par nom ou prénom de client
  var filterByClientName = function filterByClientName(list) {
    return list.filter(function (c) {
      var fullname = c.client ? "".concat(c.client.nom, " ").concat(c.client.prenom).toLowerCase() : '';
      return fullname.includes(searchQuery.toLowerCase());
    });
  };
  var filteredNonApprouves = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(function () {
    return filterByClientName(creditsNonApprouves);
  }, [creditsNonApprouves, searchQuery]);
  var filteredApprouves = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(function () {
    return filterByClientName(creditsApprouves);
  }, [creditsApprouves, searchQuery]);
  var filteredHistorique = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(function () {
    return filterByClientName(historique);
  }, [historique, searchQuery]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_Layouts_AdminLayout_jsx__WEBPACK_IMPORTED_MODULE_2__["default"], {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
      className: "container-fluid px-4 py-3 bg-light-subtle",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
        className: "d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4 gap-3",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("h1", {
            className: "h4 mb-1 text-dark fw-bold",
            children: "Gestion des dossiers de cr\xE9dit"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("p", {
            className: "text-muted small mb-0",
            children: "Suivi des demandes, arbitrage des dossiers et archivage des flux actifs."
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          style: {
            maxWidth: '300px'
          },
          className: "w-100",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
            className: "input-group input-group-sm",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
              className: "input-group-text bg-white text-muted border-end-0",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("i", {
                className: "bi bi-search"
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
              type: "text",
              className: "form-control form-control-sm border-start-0 ps-0 text-muted",
              placeholder: "Rechercher un client...",
              value: searchQuery,
              onChange: function onChange(e) {
                return setSearchQuery(e.target.value);
              }
            })]
          })
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("ul", {
        className: "nav nav-tabs border-bottom-0 small mb-3 gap-1",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
          className: "nav-item",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
            className: "nav-link border-0 rounded-3 px-3 py-2 fw-medium ".concat(tab === 'pending' ? 'bg-white shadow-sm text-dark border-bottom-0' : 'text-muted bg-transparent'),
            onClick: function onClick() {
              return setTab('pending');
            },
            children: "En attente d'approbation"
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
          className: "nav-item",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
            className: "nav-link border-0 rounded-3 px-3 py-2 fw-medium ".concat(tab === 'approved' ? 'bg-white shadow-sm text-dark border-bottom-0' : 'text-muted bg-transparent'),
            onClick: function onClick() {
              return setTab('approved');
            },
            children: "Approuv\xE9s / En attente de fonds"
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
          className: "nav-item",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
            className: "nav-link border-0 rounded-3 px-3 py-2 fw-medium ".concat(tab === 'history' ? 'bg-white shadow-sm text-dark border-bottom-0' : 'text-muted bg-transparent'),
            onClick: function onClick() {
              return setTab('history');
            },
            children: "Archives & Actifs"
          })
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
        className: "tab-content",
        children: [tab === 'pending' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          className: "card shadow-sm border-light rounded-3 overflow-hidden",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
            className: "table-responsive",
            style: {
              maxHeight: '500px'
            },
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("table", {
              className: "table table-sm table-hover align-middle mb-0 text-secondary",
              style: {
                fontSize: '13px'
              },
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("thead", {
                className: "table-light sticky-top",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                  className: "text-muted text-uppercase",
                  style: {
                    fontSize: '11px',
                    trackingMultiplier: 1
                  },
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "ps-3 py-2.5 text-start",
                    style: {
                      width: '80px'
                    },
                    children: "#"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-start",
                    children: "Client"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-end",
                    style: {
                      width: '180px'
                    },
                    children: "Montant demand\xE9"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-start ps-4",
                    children: "Conditions"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-center",
                    style: {
                      width: '150px'
                    },
                    children: "Statut"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-end pe-3",
                    style: {
                      width: '140px'
                    },
                    children: "Actions"
                  })]
                })
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("tbody", {
                children: filteredNonApprouves.length > 0 ? filteredNonApprouves.map(function (c) {
                  var _c$montant_demande, _c$periodicite, _c$nombre_echeances, _c$mode, _c$statut;
                  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("td", {
                      className: "ps-3 fw-semibold text-dark",
                      children: ["#", c.id]
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-dark fw-medium",
                      children: c.client ? "".concat(c.client.nom, " ").concat(c.client.prenom) : '—'
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-end font-monospace fw-semibold text-dark",
                      children: formatCurrency((_c$montant_demande = c.montant_demande) !== null && _c$montant_demande !== void 0 ? _c$montant_demande : c.montant)
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("td", {
                      className: "ps-4 text-capitalize text-muted",
                      children: [(_c$periodicite = c.periodicite) !== null && _c$periodicite !== void 0 ? _c$periodicite : '—', " \u2022 ", (_c$nombre_echeances = c.nombre_echeances) !== null && _c$nombre_echeances !== void 0 ? _c$nombre_echeances : '—', " \xE9chs \u2022 ", (_c$mode = c.mode) !== null && _c$mode !== void 0 ? _c$mode : '—']
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-center",
                      children: renderStatusBadge((_c$statut = c.statut) !== null && _c$statut !== void 0 ? _c$statut : c.status)
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-end pe-3",
                      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_1__.Link, {
                        href: "/admin/prets/".concat(c.id),
                        className: "btn btn-sm btn-outline-secondary px-2 py-1",
                        style: {
                          fontSize: '12px'
                        },
                        children: ["Arbitrer ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("i", {
                          className: "bi bi-chevron-right ms-1"
                        })]
                      })
                    })]
                  }, c.id);
                }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("tr", {
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                    colSpan: "6",
                    className: "text-center text-muted py-4 small",
                    children: "Aucun dossier en attente d'approbation."
                  })
                })
              })]
            })
          })
        }), tab === 'approved' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          className: "card shadow-sm border-light rounded-3 overflow-hidden",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
            className: "table-responsive",
            style: {
              maxHeight: '500px'
            },
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("table", {
              className: "table table-sm table-hover align-middle mb-0 text-secondary",
              style: {
                fontSize: '13px'
              },
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("thead", {
                className: "table-light sticky-top",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                  className: "text-muted text-uppercase",
                  style: {
                    fontSize: '11px'
                  },
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "ps-3 py-2.5 text-start",
                    style: {
                      width: '80px'
                    },
                    children: "#"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-start",
                    children: "Client"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-end",
                    style: {
                      width: '180px'
                    },
                    children: "Montant accord\xE9"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-start ps-4",
                    children: "Conditions d'octroi"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-center",
                    style: {
                      width: '150px'
                    },
                    children: "Statut"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-end pe-3",
                    style: {
                      width: '140px'
                    },
                    children: "Actions"
                  })]
                })
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("tbody", {
                children: filteredApprouves.length > 0 ? filteredApprouves.map(function (c) {
                  var _ref, _c$montant_accorde, _c$periodicite2, _c$nombre_echeances2, _c$mode2, _c$statut2;
                  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("td", {
                      className: "ps-3 fw-semibold text-dark",
                      children: ["#", c.id]
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-dark fw-medium",
                      children: c.client ? "".concat(c.client.nom, " ").concat(c.client.prenom) : '—'
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-end font-monospace fw-bold text-success",
                      children: formatCurrency((_ref = (_c$montant_accorde = c.montant_accorde) !== null && _c$montant_accorde !== void 0 ? _c$montant_accorde : c.montant_demande) !== null && _ref !== void 0 ? _ref : c.montant)
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("td", {
                      className: "ps-4 text-capitalize text-muted",
                      children: [(_c$periodicite2 = c.periodicite) !== null && _c$periodicite2 !== void 0 ? _c$periodicite2 : '—', " \u2022 ", (_c$nombre_echeances2 = c.nombre_echeances) !== null && _c$nombre_echeances2 !== void 0 ? _c$nombre_echeances2 : '—', " \xE9chs \u2022 ", (_c$mode2 = c.mode) !== null && _c$mode2 !== void 0 ? _c$mode2 : '—']
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-center",
                      children: renderStatusBadge((_c$statut2 = c.statut) !== null && _c$statut2 !== void 0 ? _c$statut2 : c.status)
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-end pe-3",
                      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_1__.Link, {
                        href: "/admin/prets/".concat(c.id),
                        className: "btn btn-sm btn-outline-primary px-2 py-1",
                        style: {
                          fontSize: '12px'
                        },
                        children: ["D\xE9caissement ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("i", {
                          className: "bi bi-chevron-right ms-1"
                        })]
                      })
                    })]
                  }, c.id);
                }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("tr", {
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                    colSpan: "6",
                    className: "text-center text-muted py-4 small",
                    children: "Aucun dossier approuv\xE9 en attente de fonds."
                  })
                })
              })]
            })
          })
        }), tab === 'history' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          className: "card shadow-sm border-light rounded-3 overflow-hidden",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
            className: "table-responsive",
            style: {
              maxHeight: '500px'
            },
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("table", {
              className: "table table-sm table-hover align-middle mb-0 text-secondary",
              style: {
                fontSize: '13px'
              },
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("thead", {
                className: "table-light sticky-top",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                  className: "text-muted text-uppercase",
                  style: {
                    fontSize: '11px'
                  },
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "ps-3 py-2.5 text-start",
                    style: {
                      width: '80px'
                    },
                    children: "#"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-start",
                    children: "Client"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-end",
                    style: {
                      width: '180px'
                    },
                    children: "Montant final"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-start ps-4",
                    children: "Conditions"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-center",
                    style: {
                      width: '150px'
                    },
                    children: "Statut"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                    className: "text-end pe-3",
                    style: {
                      width: '140px'
                    },
                    children: "Derni\xE8re mise \xE0 jour"
                  })]
                })
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("tbody", {
                children: filteredHistorique.length > 0 ? filteredHistorique.map(function (c) {
                  var _ref2, _c$montant_accorde2, _c$periodicite3, _c$nombre_echeances3, _c$mode3, _c$statut3;
                  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("td", {
                      className: "ps-3 fw-semibold text-muted",
                      children: ["#", c.id]
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-dark",
                      children: c.client ? "".concat(c.client.nom, " ").concat(c.client.prenom) : '—'
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-end font-monospace fw-semibold text-dark",
                      children: formatCurrency((_ref2 = (_c$montant_accorde2 = c.montant_accorde) !== null && _c$montant_accorde2 !== void 0 ? _c$montant_accorde2 : c.montant_demande) !== null && _ref2 !== void 0 ? _ref2 : c.montant)
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("td", {
                      className: "ps-4 text-capitalize text-muted",
                      children: [(_c$periodicite3 = c.periodicite) !== null && _c$periodicite3 !== void 0 ? _c$periodicite3 : '—', " \u2022 ", (_c$nombre_echeances3 = c.nombre_echeances) !== null && _c$nombre_echeances3 !== void 0 ? _c$nombre_echeances3 : '—', " \xE9chs \u2022 ", (_c$mode3 = c.mode) !== null && _c$mode3 !== void 0 ? _c$mode3 : '—']
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-center",
                      children: renderStatusBadge((_c$statut3 = c.statut) !== null && _c$statut3 !== void 0 ? _c$statut3 : c.status)
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      className: "text-end pe-3 font-monospace text-muted",
                      style: {
                        fontSize: '12px'
                      },
                      children: formatDateToFR(c.updated_at)
                    })]
                  }, c.id);
                }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("tr", {
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                    colSpan: "6",
                    className: "text-center text-muted py-4 small",
                    children: "Aucun historique disponible dans cette section."
                  })
                })
              })]
            })
          })
        })]
      })]
    })
  });
}

/***/ })

}]);