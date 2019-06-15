/* eslint-disable */

export default ({ app }) => {
  if (process.env.NODE_ENV !== 'production') return

  (function(h,o,t,j,a,r){
      h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
      h._hjSettings={hjid:123457,hjsv:3};
      a=o.getElementsByTagName('head')[0];
      r=o.createElement('script');r.defer=1;
      r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
      a.appendChild(r);
  })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
}
