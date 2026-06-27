<?php
/**
 * BoxPHP Framework
 *
 * Copyright 2026 BoxPHP
 * By tvjojo, asterhuang, 黄波涛; 5viv.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Worker 接口
 */
namespace BoxPHP\Server\Worker;

interface WorkerInterface
{
    /**
     * 获取 Worker 名称
     */
    public function getName(): string;

    /**
     * 获取监听地址
     */
    public function getListen(): string;

    /**
     * 获取进程数
     */
    public function getCount(): int;

    /**
     * 启动 Worker
     */
    public function start(): void;

    /**
     * 停止 Worker
     */
    public function stop(): void;

    /**
     * 重启 Worker
     */
    public function restart(): void;

    /**
     * 注册消息处理器
     */
    public function onMessage(callable $handler): static;

    /**
     * 注册启动回调
     */
    public function onStart(callable $handler): static;

    /**
     * 注册停止回调
     */
    public function onStop(callable $handler): static;

    /**
     * 注册连接回调
     */
    public function onConnect(callable $handler): static;

    /**
     * 注册关闭回调
     */
    public function onClose(callable $handler): static;

    /**
     * 获取统计信息
     */
    public function getStats(): array;

    /**
     * 是否正在运行
     */
    public function isRunning(): bool;
}