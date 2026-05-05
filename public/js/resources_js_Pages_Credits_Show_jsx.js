"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["resources_js_Pages_Credits_Show_jsx"],{

/***/ "./resources/js/Components/BootstrapModal.jsx":
/*!****************************************************!*\
  !*** ./resources/js/Components/BootstrapModal.jsx ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ BootstrapModal)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");


function BootstrapModal(_ref) {
  var show = _ref.show,
    title = _ref.title,
    body = _ref.body,
    onConfirm = _ref.onConfirm,
    onClose = _ref.onClose,
    _ref$confirmText = _ref.confirmText,
    confirmText = _ref$confirmText === void 0 ? 'Oui' : _ref$confirmText,
    _ref$cancelText = _ref.cancelText,
    cancelText = _ref$cancelText === void 0 ? 'Annuler' : _ref$cancelText,
    _ref$confirmVariant = _ref.confirmVariant,
    confirmVariant = _ref$confirmVariant === void 0 ? 'primary' : _ref$confirmVariant,
    _ref$hideCancel = _ref.hideCancel,
    hideCancel = _ref$hideCancel === void 0 ? false : _ref$hideCancel;
  if (!show) {
    return null;
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
    className: "modal fade show d-block",
    tabIndex: "-1",
    role: "dialog",
    "aria-modal": "true",
    style: {
      backgroundColor: 'rgba(0,0,0,0.5)'
    },
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
      className: "modal-dialog modal-dialog-centered",
      role: "document",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
        className: "modal-content",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
          className: "modal-header",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("h5", {
            className: "modal-title",
            children: title
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
            type: "button",
            className: "btn-close",
            "aria-label": "Fermer",
            onClick: onClose
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
          className: "modal-body",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("p", {
            children: body
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
          className: "modal-footer",
          children: [!hideCancel && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
            type: "button",
            className: "btn btn-secondary",
            onClick: onClose,
            children: cancelText
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
            type: "button",
            className: "btn btn-".concat(confirmVariant),
            onClick: onConfirm,
            children: confirmText
          })]
        })]
      })
    })
  });
}

/***/ }),

/***/ "./resources/js/Layouts/AdminLayout.jsx":
/*!**********************************************!*\
  !*** ./resources/js/Layouts/AdminLayout.jsx ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ AdminLayout)
/* harmony export */ });
/* harmony import */ var _inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @inertiajs/inertia-react */ "./node_modules/@inertiajs/inertia-react/dist/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");


function AdminLayout(_ref) {
  var children = _ref.children;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("nav", {
      className: "navbar navbar-expand-lg navbar-dark bg-dark",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
        className: "container-fluid px-4",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.Link, {
          href: "/admin/dashboard",
          className: "navbar-brand",
          children: "NANA ECO CONSULTING"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
          className: "navbar-toggler",
          type: "button",
          "data-bs-toggle": "collapse",
          "data-bs-target": "#navbarNav",
          "aria-controls": "navbarNav",
          "aria-expanded": "false",
          "aria-label": "Toggle navigation",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
            className: "navbar-toggler-icon"
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
          className: "collapse navbar-collapse",
          id: "navbarNav",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("ul", {
            className: "navbar-nav ms-auto",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
              className: "nav-item",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.Link, {
                href: "/admin/clients",
                className: "nav-link",
                children: "Clients"
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
              className: "nav-item",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.Link, {
                href: "/admin/carnets",
                className: "nav-link",
                children: "Carnets"
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
              className: "nav-item",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.Link, {
                href: "/admin/credits",
                className: "nav-link",
                children: "Cr\xE9dits"
              })
            })]
          })
        })]
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("main", {
      className: "container mt-4",
      children: children
    })]
  });
}

/***/ }),

/***/ "./resources/js/Pages/Credits/Show.jsx":
/*!*********************************************!*\
  !*** ./resources/js/Pages/Credits/Show.jsx ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Show)
/* harmony export */ });
/* harmony import */ var _inertiajs_inertia__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @inertiajs/inertia */ "./node_modules/@inertiajs/inertia/dist/index.js");
/* harmony import */ var _inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/inertia-react */ "./node_modules/@inertiajs/inertia-react/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Layouts_AdminLayout_jsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../Layouts/AdminLayout.jsx */ "./resources/js/Layouts/AdminLayout.jsx");
/* harmony import */ var _Components_BootstrapModal_jsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../Components/BootstrapModal.jsx */ "./resources/js/Components/BootstrapModal.jsx");
/* harmony import */ var _Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../Utils/creditHelpers */ "./resources/js/Utils/creditHelpers.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }







function Show(_ref) {
  var _credit$payments$data, _credit$payments, _credit$payments2, _credit$penalty_amoun, _credit$penalty_amoun2, _credit$emergency_wit;
  var credit = _ref.credit;
  var flash = (0,_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_1__.usePage)().props.flash;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(false),
    _useState2 = _slicedToArray(_useState, 2),
    confirmOpen = _useState2[0],
    setConfirmOpen = _useState2[1];
  var payments = Array.isArray(credit.payments) ? credit.payments : (_credit$payments$data = (_credit$payments = credit.payments) === null || _credit$payments === void 0 ? void 0 : _credit$payments.data) !== null && _credit$payments$data !== void 0 ? _credit$payments$data : [];
  var pagination = Array.isArray(credit.payments) ? null : (_credit$payments2 = credit.payments) !== null && _credit$payments2 !== void 0 ? _credit$payments2 : null;
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(function () {
      return payments.reduce(function (acc, payment) {
        var _ref2, _payment$computed_pen;
        return _objectSpread(_objectSpread({}, acc), {}, _defineProperty({}, payment.id, (_ref2 = (_payment$computed_pen = payment.computed_penalty) !== null && _payment$computed_pen !== void 0 ? _payment$computed_pen : payment.penalite) !== null && _ref2 !== void 0 ? _ref2 : 0));
      }, {});
    }),
    _useState4 = _slicedToArray(_useState3, 2),
    penalties = _useState4[0],
    setPenalties = _useState4[1];
  var _useState5 = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(function () {
      return payments.reduce(function (acc, payment) {
        return _objectSpread(_objectSpread({}, acc), {}, _defineProperty({}, payment.id, ''));
      }, {});
    }),
    _useState6 = _slicedToArray(_useState5, 2),
    paymentAmounts = _useState6[0],
    setPaymentAmounts = _useState6[1];
  (0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(function () {
    setPenalties(payments.reduce(function (acc, payment) {
      var _ref3, _payment$computed_pen2;
      return _objectSpread(_objectSpread({}, acc), {}, _defineProperty({}, payment.id, (_ref3 = (_payment$computed_pen2 = payment.computed_penalty) !== null && _payment$computed_pen2 !== void 0 ? _payment$computed_pen2 : payment.penalite) !== null && _ref3 !== void 0 ? _ref3 : 0));
    }, {}));
    setPaymentAmounts(payments.reduce(function (acc, payment) {
      return _objectSpread(_objectSpread({}, acc), {}, _defineProperty({}, payment.id, ''));
    }, {}));
  }, [credit.payments]);
  var formatCurrency = function formatCurrency(value) {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XAF',
      maximumFractionDigits: 0
    }).format(value);
  };
  var paymentClass = function paymentClass(status) {
    switch (status) {
      case 'paid':
        return 'badge bg-success';
      case 'late':
        return 'badge bg-danger';
      case 'pending':
        return 'badge bg-warning text-dark';
      case 'partiel':
        return 'badge bg-info text-dark';
      default:
        return 'badge bg-secondary';
    }
  };
  var creditStatusLabel = function creditStatusLabel(status) {
    switch (status) {
      case 'approved':
        return 'Approuvé';
      case 'active':
        return 'Actif';
      case 'pending':
        return 'En attente';
      case 'in_arrears':
        return 'En retard';
      case 'closed':
        return 'Clôturé';
      case 'rejected':
        return 'Rejeté';
      default:
        return 'Inconnu';
    }
  };
  var paymentStatusLabel = function paymentStatusLabel(status) {
    switch (status) {
      case 'paid':
        return 'Payé';
      case 'late':
        return 'En retard';
      case 'pending':
        return 'En attente';
      case 'partiel':
        return 'Partiel';
      default:
        return 'Inconnu';
    }
  };
  var canPayInstallment = function canPayInstallment(payment) {
    if (payment.status === 'paid') {
      return false;
    }
    if (payment.can_pay !== undefined) {
      return payment.can_pay;
    }
    return payments.filter(function (p) {
      return p.echeance < payment.echeance;
    }).every(function (p) {
      return p.status === 'paid';
    });
  };
  var approve = function approve() {
    setConfirmOpen(true);
  };
  var confirmApprove = function confirmApprove() {
    setConfirmOpen(false);
    _inertiajs_inertia__WEBPACK_IMPORTED_MODULE_0__.Inertia.post("/admin/credits/".concat(credit.id, "/approve"));
  };
  var savePenalty = function savePenalty(payment) {
    var _ref4, _penalties$payment$id;
    var amount = Number((_ref4 = (_penalties$payment$id = penalties[payment.id]) !== null && _penalties$payment$id !== void 0 ? _penalties$payment$id : payment.computed_penalty) !== null && _ref4 !== void 0 ? _ref4 : 0);
    if (Number.isNaN(amount) || amount < 0) {
      return;
    }
    _inertiajs_inertia__WEBPACK_IMPORTED_MODULE_0__.Inertia.patch("/admin/credits/".concat(credit.id, "/payments/").concat(payment.id), {
      penalite: amount
    }, {
      preserveScroll: true,
      onSuccess: function onSuccess() {
        setPenalties(function (prev) {
          return _objectSpread(_objectSpread({}, prev), {}, _defineProperty({}, payment.id, amount));
        });
      }
    });
  };
  var savePayment = function savePayment(payment) {
    var amount = Number(paymentAmounts[payment.id]);
    if (Number.isNaN(amount) || amount <= 0) {
      return;
    }
    _inertiajs_inertia__WEBPACK_IMPORTED_MODULE_0__.Inertia.patch("/admin/credits/".concat(credit.id, "/payments/").concat(payment.id), {
      montant_paye: amount
    }, {
      preserveScroll: true,
      onSuccess: function onSuccess() {
        setPaymentAmounts(function (prev) {
          return _objectSpread(_objectSpread({}, prev), {}, _defineProperty({}, payment.id, ''));
        });
      }
    });
  };
  var closeConfirm = function closeConfirm() {
    setConfirmOpen(false);
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_Layouts_AdminLayout_jsx__WEBPACK_IMPORTED_MODULE_3__["default"], {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
      children: [flash.success && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
        className: "alert alert-success",
        role: "alert",
        children: flash.success
      }), flash.error && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
        className: "alert alert-danger",
        role: "alert",
        children: flash.error
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
        className: "d-flex justify-content-between align-items-center mb-4",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("h1", {
            className: "h3",
            children: ["Cr\xE9dit #", credit.id]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
            className: "text-muted mb-0",
            children: "D\xE9tail du dossier de cr\xE9dit et \xE9ch\xE9ancier."
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_1__.Link, {
            href: "/admin/credits",
            className: "btn btn-outline-secondary me-2",
            children: "Retour"
          }), credit.statut === 'pending' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("button", {
            className: "btn btn-success",
            onClick: approve,
            children: "Approuver"
          })]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_Components_BootstrapModal_jsx__WEBPACK_IMPORTED_MODULE_4__["default"], {
        show: confirmOpen,
        title: "Confirmation",
        body: "Approuver ce cr\xE9dit et activer l\u2019\xE9ch\xE9ancier ?",
        onConfirm: confirmApprove,
        onClose: closeConfirm,
        confirmText: "Oui, approuver",
        cancelText: "Annuler",
        confirmVariant: "success"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
        className: "row g-3 mb-4",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
          className: "col-md-4",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
            className: "card shadow-sm p-3",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("h5", {
              className: "mb-3",
              children: "Informations"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "Client :"
              }), " ", credit.client.nom, " ", credit.client.prenom]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "Statut :"
              }), " ", creditStatusLabel(credit.statut)]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "Montant demand\xE9 :"
              }), " ", formatCurrency(credit.montant_demande)]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "Taux appliqu\xE9 :"
              }), " ", credit.taux, "%"]
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
          className: "col-md-4",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
            className: "card shadow-sm p-3",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("h5", {
              className: "mb-3",
              children: "Conditions"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "Type :"
              }), " ", credit.type]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "Mode :"
              }), " ", credit.mode]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "P\xE9riodicit\xE9 :"
              }), " ", credit.periodicite]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "\xC9ch\xE9ances :"
              }), " ", credit.nombre_echeances]
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
          className: "col-md-4",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
            className: "card shadow-sm p-3",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("h5", {
              className: "mb-3",
              children: "Chiffres cl\xE9s"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "Int\xE9r\xEAt total :"
              }), " ", formatCurrency(credit.interet_total)]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "P\xE9nalit\xE9s totales :"
              }), " ", formatCurrency((_credit$penalty_amoun = credit.penalty_amount) !== null && _credit$penalty_amoun !== void 0 ? _credit$penalty_amoun : 0)]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "Montant total d\xFB :"
              }), ' ', formatCurrency(Number(credit.montant_accorde) + Number(credit.interet_total) + Number((_credit$penalty_amoun2 = credit.penalty_amount) !== null && _credit$penalty_amoun2 !== void 0 ? _credit$penalty_amoun2 : 0))]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
                children: "Montant rembours\xE9 :"
              }), " ", formatCurrency(credit.montant_rembourse)]
            })]
          })
        })]
      }), ((_credit$emergency_wit = credit.emergency_withdrawal_summary) === null || _credit$emergency_wit === void 0 ? void 0 : _credit$emergency_wit.length) > 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.Fragment, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
          className: "alert alert-warning",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
            children: "Pr\xE9l\xE8vement automatique appliqu\xE9 :"
          }), " des sommes ont \xE9t\xE9 pr\xE9lev\xE9es sur l'\xE9pargne disponible pour couvrir une ou plusieurs \xE9ch\xE9ances en d\xE9faut."]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
          className: "card shadow-sm mb-4 border-danger",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
            className: "card-header bg-white text-danger",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
              children: "Pr\xE9l\xE8vements de secours automatiques"
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
            className: "card-body",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
              className: "mb-3 text-muted",
              children: "Les montants suivants ont \xE9t\xE9 retir\xE9s automatiquement et affect\xE9s aux paiements en d\xE9faut."
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("ul", {
              className: "list-group list-group-flush",
              children: credit.emergency_withdrawal_summary.map(function (item, index) {
                return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("li", {
                  className: "list-group-item px-0",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("strong", {
                    children: ["\xC9ch\xE9ance #", item.echeance, " :"]
                  }), " ", formatCurrency(item.amount_withdrawn), " pr\xE9lev\xE9s, dont ", formatCurrency(item.amount_applied), " appliqu\xE9s au paiement."]
                }, index);
              })
            })]
          })]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
        className: "card shadow-sm",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
          className: "card-header bg-white",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("strong", {
            children: "\xC9ch\xE9ancier"
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
          className: "card-body p-0",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("table", {
            className: "table mb-0",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("thead", {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("tr", {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("th", {
                  children: "#"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("th", {
                  children: "Date"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("th", {
                  children: "Principal"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("th", {
                  children: "Int\xE9r\xEAts"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("th", {
                  children: "Total"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("th", {
                  children: "P\xE9nalit\xE9"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("th", {
                  children: "Pay\xE9"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("th", {
                  children: "Reste"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("th", {
                  children: "Paiement"
                })]
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("tbody", {
              children: [payments.map(function (payment) {
                var _payment$penalite, _payment$montant_paye, _ref5, _penalties$payment$id2, _ref6, _payment$computed_pen3, _paymentAmounts$payme, _payment$display_stat, _payment$display_stat2, _payment$display_stat3, _payment$display_stat4;
                var totalDue = Number(payment.montant_total) + Number((_payment$penalite = payment.penalite) !== null && _payment$penalite !== void 0 ? _payment$penalite : 0);
                var paidAmount = Number((_payment$montant_paye = payment.montant_paye) !== null && _payment$montant_paye !== void 0 ? _payment$montant_paye : 0);
                var remaining = Math.max(0, totalDue - paidAmount);
                return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("tr", {
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("td", {
                    children: payment.echeance
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("td", {
                    children: (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_5__.formatDateToFR)(payment.due_date)
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("td", {
                    children: formatCurrency(payment.montant_principal)
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("td", {
                    children: formatCurrency(payment.montant_interets)
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("td", {
                    children: formatCurrency(payment.montant_total)
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("td", {
                    children: payment.status !== 'paid' ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
                      className: "input-group input-group-sm",
                      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("input", {
                        type: "number",
                        step: "0.01",
                        min: "0",
                        className: "form-control",
                        value: (_ref5 = (_penalties$payment$id2 = penalties[payment.id]) !== null && _penalties$payment$id2 !== void 0 ? _penalties$payment$id2 : payment.computed_penalty) !== null && _ref5 !== void 0 ? _ref5 : 0,
                        onChange: function onChange(e) {
                          return setPenalties(function (prev) {
                            return _objectSpread(_objectSpread({}, prev), {}, _defineProperty({}, payment.id, e.target.value));
                          });
                        }
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("button", {
                        type: "button",
                        className: "btn btn-outline-secondary",
                        onClick: function onClick() {
                          return savePenalty(payment);
                        },
                        children: "Enregistrer"
                      })]
                    }) : formatCurrency((_ref6 = (_payment$computed_pen3 = payment.computed_penalty) !== null && _payment$computed_pen3 !== void 0 ? _payment$computed_pen3 : payment.penalite) !== null && _ref6 !== void 0 ? _ref6 : 0)
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("td", {
                    children: formatCurrency(paidAmount)
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("td", {
                    children: formatCurrency(remaining)
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("td", {
                    children: [payment.status !== 'paid' ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
                      className: "d-flex gap-2 align-items-center",
                      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
                        className: "input-group input-group-sm",
                        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("input", {
                          type: "number",
                          step: "0.01",
                          min: "0",
                          className: "form-control",
                          placeholder: "Montant",
                          value: (_paymentAmounts$payme = paymentAmounts[payment.id]) !== null && _paymentAmounts$payme !== void 0 ? _paymentAmounts$payme : '',
                          onChange: function onChange(e) {
                            return setPaymentAmounts(function (prev) {
                              return _objectSpread(_objectSpread({}, prev), {}, _defineProperty({}, payment.id, e.target.value));
                            });
                          },
                          disabled: !canPayInstallment(payment)
                        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("button", {
                          type: "button",
                          className: "btn btn-outline-secondary",
                          onClick: function onClick() {
                            return savePayment(payment);
                          },
                          disabled: !canPayInstallment(payment),
                          children: "Payer"
                        })]
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("span", {
                        className: paymentClass((_payment$display_stat = payment.display_status) !== null && _payment$display_stat !== void 0 ? _payment$display_stat : payment.status),
                        children: paymentStatusLabel((_payment$display_stat2 = payment.display_status) !== null && _payment$display_stat2 !== void 0 ? _payment$display_stat2 : payment.status)
                      })]
                    }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("span", {
                      className: paymentClass((_payment$display_stat3 = payment.display_status) !== null && _payment$display_stat3 !== void 0 ? _payment$display_stat3 : payment.status),
                      children: paymentStatusLabel((_payment$display_stat4 = payment.display_status) !== null && _payment$display_stat4 !== void 0 ? _payment$display_stat4 : payment.status)
                    }), !canPayInstallment(payment) && payment.status !== 'paid' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
                      className: "small text-muted mt-1",
                      children: "Paiement bloqu\xE9 tant que l\u2019\xE9ch\xE9ance pr\xE9c\xE9dente n\u2019est pas r\xE9gl\xE9e."
                    })]
                  })]
                }, payment.id);
              }), payments.length === 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("tr", {
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("td", {
                  colSpan: "9",
                  className: "text-center py-4",
                  children: "Aucun \xE9ch\xE9ancier disponible."
                })
              })]
            })]
          })
        })]
      }), pagination && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
        className: "mt-3 d-flex justify-content-end gap-2",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("button", {
          className: "btn btn-outline-secondary",
          onClick: function onClick() {
            return _inertiajs_inertia__WEBPACK_IMPORTED_MODULE_0__.Inertia.visit(pagination.prev_page_url);
          },
          disabled: !pagination.prev_page_url,
          children: "Pr\xE9c\xE9dent"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("button", {
          className: "btn btn-outline-secondary",
          onClick: function onClick() {
            return _inertiajs_inertia__WEBPACK_IMPORTED_MODULE_0__.Inertia.visit(pagination.next_page_url);
          },
          disabled: !pagination.next_page_url,
          children: "Suivant"
        })]
      })]
    })
  });
}

/***/ }),

/***/ "./resources/js/Utils/creditHelpers.js":
/*!*********************************************!*\
  !*** ./resources/js/Utils/creditHelpers.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "formatCurrency": () => (/* binding */ formatCurrency),
/* harmony export */   "parseDateString": () => (/* binding */ parseDateString),
/* harmony export */   "formatDateToFR": () => (/* binding */ formatDateToFR),
/* harmony export */   "periodDays": () => (/* binding */ periodDays),
/* harmony export */   "calculateRate": () => (/* binding */ calculateRate),
/* harmony export */   "buildScheduleFromForm": () => (/* binding */ buildScheduleFromForm)
/* harmony export */ });
function formatCurrency(value) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    maximumFractionDigits: 0
  }).format(value || 0);
}
function parseDateString(value) {
  if (!value) {
    return new Date(NaN);
  }
  if (value instanceof Date) {
    return value;
  }
  var stringValue = String(value).trim();
  var isoDate = stringValue.replace(' ', 'T');
  var parsedIso = new Date(isoDate);
  if (!Number.isNaN(parsedIso.getTime())) {
    return parsedIso;
  }
  var parts = stringValue.split(/[-T:\s]/).map(Number).filter(function (part) {
    return !Number.isNaN(part);
  });
  if (parts.length >= 3) {
    return new Date(parts[0], parts[1] - 1, parts[2]);
  }
  return new Date(NaN);
}
function formatDateToFR(value) {
  if (!value) return '';
  var date = typeof value === 'string' ? parseDateString(value) : value;
  if (date instanceof Date && Number.isNaN(date.getTime())) {
    return '';
  }
  return new Intl.DateTimeFormat('fr-FR').format(date);
}
function periodDays(periodicite) {
  return periodicite === 'quinzaine' ? 15 : 30;
}
function calculateRate(taux, tauxManuel) {
  var base = Number(taux) || 0;
  var manual = tauxManuel !== null && tauxManuel !== undefined && tauxManuel !== '' ? Number(tauxManuel) : null;
  return manual > 0 ? manual : base;
}
function buildScheduleFromForm(form) {
  var montant = Number(form.montant_demande || 0);
  var taux = calculateRate(form.taux, form.taux_manuelle) / 100;
  var nombre = Math.max(1, Number(form.nombre_echeances || 1));
  var mode = form.mode || 'fixe';
  var periodDaysCount = periodDays(form.periodicite || 'mensuelle');
  var start = form.date_debut || new Date().toISOString().slice(0, 10);
  var startDate = parseDateString(start);
  var principalBase = Math.round(montant / nombre * 100) / 100;
  var remaining = montant;
  var schedule = [];
  for (var i = 1; i <= nombre; i += 1) {
    var interest = mode === 'degressif' ? Math.round(remaining * taux * 100) / 100 : Math.round(montant * taux * 100) / 100;
    var principal = i === nombre ? Math.round(remaining * 100) / 100 : principalBase;
    var total = Math.round((principal + interest) * 100) / 100;
    var dueDate = new Date(startDate);
    dueDate.setDate(dueDate.getDate() + (i - 1) * periodDaysCount);
    schedule.push({
      numero: i,
      date: dueDate.toISOString().slice(0, 10),
      principal: principal,
      interest: interest,
      total: total
    });
    remaining = Math.round((remaining - principal) * 100) / 100;
  }
  return schedule;
}

/***/ })

}]);