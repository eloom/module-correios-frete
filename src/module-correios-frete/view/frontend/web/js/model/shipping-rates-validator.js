define(["jquery","mageUtils","Eloom_CorreiosFrete/js/model/shipping-rates-validation-rules","mage/translate"],function(c,d,e,b){return{validationErrors:[],validate:function(f){var g=this;this.validationErrors=[];c.each(e.getRules(),function(a,h){h.required&&d.isEmpty(f[a])&&(a=b("Field ")+a+b(" is required."),g.validationErrors.push(a))});return!this.validationErrors.length}}});
