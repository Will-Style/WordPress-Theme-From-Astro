 
  export const get_website_url = ( url = '/', params = {}) => {
    // remove the leading slash
    url = url.replace( /^\//, '' );

    const [baseUrl, existingQuery] = url.split('?');
    const cleanUrl = baseUrl.replace(/\/?$/, '/');

    // parse existing query params (from PHP URL)
    const existingParams = new URLSearchParams(existingQuery || '');

    const onboardingData = window[`teamupdraft_onboarding`] || {}
    const version = onboardingData.is_pro ? 'pro' : 'free';
    const prefix = onboardingData.prefix || 'teamupdraft';
    const versionNr = onboardingData.version;

    const defaultParams = {
      utm_campaign: `${prefix}-${version}-${versionNr}`
    };

    // merge params (do not overwrite existing ones)
    const finalParams = new URLSearchParams(existingParams);
    const mergedParams = Object.assign({}, defaultParams, params);

    Object.entries(mergedParams).forEach(([key, value]) => {
       if (!finalParams.has(key)) {
              finalParams.set(key, value);
       }
    });

    return cleanUrl + (finalParams.toString() ? '?' + finalParams.toString() : '');
  };
  
  
  