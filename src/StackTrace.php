<?php


namespace Laravel\Telescope;


class StackTrace
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $frames;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $vendorPath;

    public static function get()
    {
        $basePath = base_path();
        $vendorPath = base_path('vendor');

        $backbacktrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);

        return new static(array_map(function ($frame) use ($basePath, $vendorPath) {
            return new StackFrame($frame, $basePath, $vendorPath);
        }, $backbacktrace), $basePath, $vendorPath);
    }

    public function __construct(array $frames, $basePath, $vendorPath)
    {
        $this->frames = collect($frames);
        $this->basePath = $basePath;
        $this->vendorPath = $vendorPath;
    }

    public function frames()
    {
        return $this->frames;
    }

    public function firstNonVendor(array $ignoredPackages = null)
    {
        $ignoredPaths = $this->getIgnoredNonVendorCallerPaths($ignoredPackages);

        return $this->frames->first(function ($frame) use ($ignoredPaths) {
            return $frame->file && ! $this->isSubdir($frame->file, $ignoredPaths);
        });
    }

    protected function getIgnoredNonVendorCallerPaths(array $ignoredPackages = null)
    {
        if (! $ignoredPackages) {
            return [ $this->vendorPath ];
        }

        return array_map(function ($ignoredPackage) {
            return "{$this->vendorPath}{$ignoredPackage}";
        }, $ignoredPackages);
    }

    protected function isSubdir($subdir, array $paths)
    {
        foreach ($paths as $path) {
            if (strpos($subdir, $path) === 0) {
                return true;
            }
        }

        return false;
    }
}