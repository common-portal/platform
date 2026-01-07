<?php

namespace App\Http\Middleware;

use App\Models\TenantAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSubdomainTenant
{
    /**
     * Handle subdomain-based tenant resolution.
     * 
     * If a subdomain matches a tenant's whitelabel_subdomain_slug,
     * store the tenant in the request for branding overrides.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain) {
            $tenant = TenantAccount::where('whitelabel_subdomain_slug', $subdomain)
                ->where('is_soft_deleted', false)
                ->first();

            if ($tenant) {
                // Store tenant in request for use in views/controllers
                $request->attributes->set('subdomain_tenant', $tenant);
                
                // Also store in session for convenience
                session(['subdomain_tenant_id' => $tenant->id]);
            }
        } else {
            // Clear any previous subdomain tenant
            session()->forget('subdomain_tenant_id');
        }

        return $next($request);
    }

    /**
     * Extract subdomain from host.
     * 
     * Base domain: common-portal.nsdb.com (4 parts)
     * Examples:
     * - client.common-portal.nsdb.com → client
     * - www.common-portal.nsdb.com → null (www is ignored)
     * - common-portal.nsdb.com → null
     * - localhost → null
     */
    protected function extractSubdomain(string $host): ?string
    {
        // Skip localhost and IP addresses
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        // Get base domain from config (default: common-portal.nsdb.com = 4 parts)
        $baseDomain = config('app.base_domain', 'common-portal.nsdb.com');
        $baseParts = count(explode('.', $baseDomain));

        $parts = explode('.', $host);

        // Need more parts than base domain for a subdomain
        if (count($parts) <= $baseParts) {
            return null;
        }

        $subdomain = $parts[0];

        // Ignore common non-tenant subdomains
        $ignored = ['www', 'api', 'admin', 'mail', 'smtp', 'ftp', 'common-portal'];
        if (in_array(strtolower($subdomain), $ignored)) {
            return null;
        }

        return strtolower($subdomain);
    }
}
