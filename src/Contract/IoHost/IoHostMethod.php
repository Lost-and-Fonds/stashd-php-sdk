<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * RPC operations declared by the io-host interface.
 */
enum IoHostMethod: string
{
    /**
     * Identifies the open staged artifact wire operation.
     */
    case OpenStagedArtifact = 'stashd:plugin/io-host.open-staged-artifact';
    /**
     * Identifies the open staging area wire operation.
     */
    case OpenStagingArea = 'stashd:plugin/io-host.open-staging-area';
    /**
     * Identifies the start helper wire operation.
     */
    case StartHelper = 'stashd:plugin/io-host.start-helper';
    /**
     * Identifies the byte stream.read wire operation.
     */
    case ByteStreamRead = 'stashd:plugin/io-host.byte-stream.read';
    /**
     * Identifies the staged writer.write wire operation.
     */
    case StagedWriterWrite = 'stashd:plugin/io-host.staged-writer.write';
    /**
     * Identifies the staged writer.finish wire operation.
     */
    case StagedWriterFinish = 'stashd:plugin/io-host.staged-writer.finish';
    /**
     * Identifies the staging area.create wire operation.
     */
    case StagingAreaCreate = 'stashd:plugin/io-host.staging-area.create';
    /**
     * Identifies the helper process.next event wire operation.
     */
    case HelperProcessNextEvent = 'stashd:plugin/io-host.helper-process.next-event';
    /**
     * Identifies the helper process.cancel wire operation.
     */
    case HelperProcessCancel = 'stashd:plugin/io-host.helper-process.cancel';
}
